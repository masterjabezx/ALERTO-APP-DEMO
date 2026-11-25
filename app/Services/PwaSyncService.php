<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\PwaSyncLog;
use App\Models\ResponseLog;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PwaSyncService
{
    public function __construct(
        protected CitizenIncidentService $citizenIncidentService,
        protected IncidentRoutingService $incidentRoutingService
    ) {}

    /**
     * Synchronize a citizen offline incident draft safely with idempotency guarantee.
     *
     * @param  array<string, mixed>  $data
     * @param  array<UploadedFile>  $attachments
     * @return array<string, mixed>
     */
    public function syncIncidentDraft(
        User $reporter,
        array $data,
        array $attachments,
        string $idempotencyKey,
        ?string $clientUuid = null
    ): array {
        // 1. Check if an incident with this idempotency_key was already created
        $existingIncident = Incident::where('idempotency_key', $idempotencyKey)
            ->where('reporter_id', $reporter->id)
            ->first();

        if ($existingIncident) {
            return [
                'status' => 'already_synced',
                'is_duplicate' => true,
                'reference_number' => $existingIncident->reference_number,
                'incident_id' => $existingIncident->id,
                'current_status' => $existingIncident->status,
                'receipt_time' => $existingIncident->submitted_at?->toIso8601String() ?? now()->toIso8601String(),
                'message' => 'Report was previously synchronized and confirmed by the Command Center.',
            ];
        }

        // 2. Check if a successful sync log exists
        $existingLog = PwaSyncLog::where('idempotency_key', $idempotencyKey)
            ->where('status', 'processed')
            ->first();

        if ($existingLog && is_array($existingLog->response_payload)) {
            return $existingLog->response_payload;
        }

        // 3. Perform atomic creation within database transaction
        return DB::transaction(function () use ($reporter, $data, $attachments, $idempotencyKey, $clientUuid) {
            $syncLog = PwaSyncLog::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'user_id' => $reporter->id,
                    'action_type' => 'incident_report',
                    'status' => 'received',
                    'request_payload' => [
                        'category_id' => $data['category_id'] ?? null,
                        'barangay_id' => $data['barangay_id'] ?? null,
                        'client_uuid' => $clientUuid,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            );

            $data['idempotency_key'] = $idempotencyKey;
            $data['client_uuid'] = $clientUuid;

            $incident = $this->citizenIncidentService->submitIncident($reporter, $data, $attachments);

            $responsePayload = [
                'status' => 'success',
                'is_duplicate' => false,
                'reference_number' => $incident->reference_number,
                'incident_id' => $incident->id,
                'current_status' => $incident->status,
                'receipt_time' => $incident->submitted_at->toIso8601String(),
                'message' => 'Incident report successfully received and registered at Command Center.',
            ];

            $syncLog->update([
                'status' => 'processed',
                'response_payload' => $responsePayload,
            ]);

            return $responsePayload;
        });
    }

    /**
     * Synchronize a queued personnel milestone update with optimistic conflict detection.
     *
     * @return array<string, mixed>
     */
    public function syncPersonnelMilestone(
        User $user,
        int $assignmentId,
        string $milestone,
        ?string $notes,
        ?float $latitude,
        ?float $longitude,
        int $expectedVersionLock,
        string $idempotencyKey
    ): array {
        $personnel = $user->personnelProfile;
        if (! $personnel) {
            throw new AccessDeniedHttpException('Authenticated user does not have a personnel profile.');
        }

        // 1. Check idempotency log
        $existingLog = PwaSyncLog::where('idempotency_key', $idempotencyKey)
            ->where('status', 'processed')
            ->first();

        if ($existingLog && is_array($existingLog->response_payload)) {
            return $existingLog->response_payload;
        }

        $assignment = IncidentAssignment::with('incident')->findOrFail($assignmentId);

        // Verify responder owns or is in the team for this assignment
        $teamIds = $personnel->teams->pluck('id')->toArray();
        $isAssigned = ($assignment->personnel_id === $personnel->id)
            || ($assignment->team_id && in_array($assignment->team_id, $teamIds, true));

        if (! $isAssigned && ! $user->isAdmin()) {
            throw new AccessDeniedHttpException('User is not authorized for this incident assignment.');
        }

        $incident = $assignment->incident;
        if (! $incident) {
            throw new InvalidArgumentException('Incident record not found for assignment.');
        }

        // 2. Conflict detection: is assignment still active?
        if ($assignment->status !== 'active') {
            $conflictPayload = [
                'status' => 'conflict',
                'conflict_type' => 'assignment_inactive',
                'reason' => "Assignment is no longer active (Current status: {$assignment->status}). The task may have been reassigned or completed by Command.",
                'assignment_status' => $assignment->status,
                'server_status' => $incident->status,
                'version_lock' => $incident->version_lock,
            ];

            PwaSyncLog::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'user_id' => $user->id,
                    'action_type' => 'personnel_milestone',
                    'status' => 'conflict',
                    'error_message' => $conflictPayload['reason'],
                    'response_payload' => $conflictPayload,
                ]
            );

            return $conflictPayload;
        }

        // 3. Conflict detection: Version lock / Legal transition check
        $allowedTransitions = [
            'dispatched' => ['acknowledged', 'on_the_way'],
            'acknowledged' => ['on_the_way', 'on_scene'],
            'on_the_way' => ['on_scene', 'in_progress'],
            'on_scene' => ['in_progress', 'resolved'],
            'in_progress' => ['resolved', 'closed'],
            'resolved' => ['closed'],
        ];

        $currentStatus = $incident->status;
        $canTransition = isset($allowedTransitions[$currentStatus]) && in_array($milestone, $allowedTransitions[$currentStatus], true);

        // If the incident version lock changed and current status is already at or past the requested milestone
        if ($incident->version_lock !== $expectedVersionLock && ! $canTransition) {
            $conflictPayload = [
                'status' => 'conflict',
                'conflict_type' => 'status_mismatch',
                'reason' => "Server status conflict: Incident is currently '{$currentStatus}' (version lock {$incident->version_lock}, expected {$expectedVersionLock}). The milestone '{$milestone}' cannot be applied.",
                'server_status' => $currentStatus,
                'requested_milestone' => $milestone,
                'version_lock' => $incident->version_lock,
            ];

            PwaSyncLog::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'user_id' => $user->id,
                    'action_type' => 'personnel_milestone',
                    'status' => 'conflict',
                    'error_message' => $conflictPayload['reason'],
                    'response_payload' => $conflictPayload,
                ]
            );

            return $conflictPayload;
        }

        // 4. Execute atomic update
        return DB::transaction(function () use ($user, $personnel, $assignment, $incident, $milestone, $notes, $latitude, $longitude, $idempotencyKey) {
            // Advance incident status first so transition is validated
            $updatedIncident = $this->incidentRoutingService->advanceMilestoneStatus(
                $user,
                $incident,
                $milestone,
                $notes ?? "Responder {$user->name} reached milestone: {$milestone} (Synchronized from offline queue)."
            );

            // Record in response_logs ('cleared' maps to 'resolved')
            $logMilestone = $milestone === 'resolved' ? 'cleared' : $milestone;
            ResponseLog::create([
                'incident_id' => $assignment->incident_id,
                'assignment_id' => $assignment->id,
                'personnel_id' => $personnel->id,
                'milestone' => $logMilestone,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'notes' => $notes,
                'recorded_at' => now(),
            ]);

            if ($milestone === 'acknowledged' && ! $assignment->acknowledged_at) {
                $assignment->update(['acknowledged_at' => now()]);
            } elseif ($milestone === 'resolved') {
                $assignment->update(['status' => 'completed']);
            }

            // Update responder availability duty status
            $dutyStatus = match ($milestone) {
                'acknowledged', 'on_the_way' => 'dispatched',
                'on_scene', 'in_progress' => 'on_scene',
                'resolved' => 'available',
                default => 'busy',
            };

            $personnel->availability()->updateOrCreate(
                ['personnel_id' => $personnel->id],
                [
                    'operational_status' => $dutyStatus,
                    'status_updated_at' => now(),
                ]
            );

            $responsePayload = [
                'status' => 'success',
                'incident_reference' => $updatedIncident->reference_number,
                'new_status' => $updatedIncident->status,
                'version_lock' => $updatedIncident->version_lock,
                'receipt_time' => now()->toIso8601String(),
                'message' => "Response milestone '{$milestone}' synchronized successfully.",
            ];

            PwaSyncLog::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'user_id' => $user->id,
                    'action_type' => 'personnel_milestone',
                    'status' => 'processed',
                    'response_payload' => $responsePayload,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            );

            return $responsePayload;
        });
    }

    /**
     * Retrieve authorized offline task cache for field personnel.
     * Strictly scopes to only assigned incidents and non-confidential details.
     *
     * @return array<string, mixed>
     */
    public function getPersonnelTaskSnapshot(User $user): array
    {
        $personnel = $user->personnelProfile;
        if (! $personnel) {
            throw new AccessDeniedHttpException('Only field personnel can download task snapshots.');
        }

        $teamIds = $personnel->teams->pluck('id')->toArray();

        $activeAssignments = IncidentAssignment::with([
            'incident.category',
            'incident.barangay',
            'incident.location',
            'office',
            'team',
        ])
            ->where('status', 'active')
            ->where(function ($q) use ($personnel, $teamIds) {
                $q->where('personnel_id', $personnel->id);
                if (! empty($teamIds)) {
                    $q->orWhereIn('team_id', $teamIds);
                }
            })
            ->latest('assigned_at')
            ->get();

        $tasks = $activeAssignments->map(function ($assignment) {
            $inc = $assignment->incident;

            return [
                'assignment_id' => $assignment->id,
                'incident_id' => $inc?->id,
                'reference_number' => $inc?->reference_number,
                'category_name' => $inc?->category?->name ?? 'General Emergency',
                'category_color' => $inc?->category?->color_code ?? '#DB4545',
                'category_icon' => $inc?->category?->icon ?? 'alert-triangle',
                'priority' => $inc?->priority ?? 'high',
                'barangay_name' => $inc?->barangay?->name ?? 'Hinoba-an',
                'description' => $inc?->description ?? '',
                'latitude' => $inc?->location?->latitude,
                'longitude' => $inc?->location?->longitude,
                'street_address' => $inc?->location?->street_address,
                'landmark' => $inc?->location?->landmark,
                'current_status' => $inc?->status ?? 'dispatched',
                'version_lock' => $inc?->version_lock ?? 1,
                'response_instructions' => $assignment->notes ?? 'Respond immediately. Observe safety protocols.',
                'assigned_at' => $assignment->assigned_at?->toIso8601String(),
                'office_name' => $assignment->office?->name,
                'team_name' => $assignment->team?->name,
            ];
        });

        return [
            'status' => 'ok',
            'tasks_count' => $tasks->count(),
            'tasks' => $tasks,
            'cached_at' => now()->toIso8601String(),
            'responder_name' => $user->name,
            'badge_number' => $personnel->badge_number,
        ];
    }

    /**
     * Retrieve authorized read-only offline summary for office accounts.
     *
     * @return array<string, mixed>
     */
    public function getOfficeSnapshot(User $user): array
    {
        $officeUser = $user->officeUsers()->with('office')->first();
        if (! $officeUser || ! $officeUser->office) {
            throw new AccessDeniedHttpException('Only authorized office users can download office snapshots.');
        }

        $office = $officeUser->office;

        // Scoped assigned incidents
        $incidents = Incident::with(['category', 'barangay', 'location'])
            ->whereHas('assignments', fn ($q) => $q->where('office_id', $office->id)->where('status', 'active'))
            ->latest('submitted_at')
            ->take(30)
            ->get()
            ->map(fn ($inc) => [
                'reference_number' => $inc->reference_number,
                'category_name' => $inc->category?->name,
                'priority' => $inc->priority,
                'status' => $inc->status,
                'barangay_name' => $inc->barangay?->name,
                'submitted_at' => $inc->submitted_at?->toIso8601String(),
                'version_lock' => $inc->version_lock,
            ]);

        return [
            'status' => 'ok',
            'office_id' => $office->id,
            'office_name' => $office->name,
            'office_level' => $officeUser->office_level,
            'incidents' => $incidents,
            'cached_at' => now()->toIso8601String(),
            'notice' => 'Read-only snapshot. Official acceptance, rejection, and dispatching require active online connection.',
        ];
    }

    /**
     * Record involuntary connection loss for authenticated user presence.
     */
    public function recordConnectionLoss(User $user): void
    {
        UserPresence::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'connection_lost',
                'last_heartbeat_at' => now(),
            ]
        );
    }
}
