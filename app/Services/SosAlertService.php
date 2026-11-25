<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CitizenSOSCreated;
use App\Events\PersonnelSOSCreated;
use App\Events\SOSAcknowledged;
use App\Events\SOSResponseAssigned;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentLocation;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use App\Models\OfficeUser;
use App\Models\PersonnelProfile;
use App\Models\SosAlert;
use App\Models\SosAssignment;
use App\Models\SosLog;
use App\Models\SosRoutingRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SosAlertService
{
    public function __construct(
        protected GeoService $geoService
    ) {}

    /**
     * Trigger an emergency SOS alert from a Citizen or Guest user.
     * Supports GPS live coordinates, labeled stale last-known coordinates, or offline sync queueing.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCitizenOrGuestSos(?User $reporter, array $data): SosAlert
    {
        if (! empty($data['idempotency_key'])) {
            $existing = SosAlert::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing->load(['barangay', 'user', 'assignments.office']);
            }
        }

        $sos = DB::transaction(function () use ($reporter, $data): SosAlert {
            $isCitizen = $reporter?->isCitizen() ?? false;
            $sourceType = $isCitizen ? 'citizen' : 'guest_citizen';
            $senderName = trim((string) ($data['sender_name'] ?? $reporter?->name ?? 'Emergency SOS Caller'));
            $contactNumber = trim((string) ($data['sender_phone'] ?? $reporter?->phone_number ?? '')) ?: 'Not provided';
            $accountLabel = $isCitizen
                ? ($reporter?->is_phone_verified ? 'Registered Citizen (Verified)' : 'Registered Citizen (Unverified)')
                : 'Guest Citizen (Unregistered)';

            $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null;
            $lng = isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null;
            $locationStatus = $this->resolveLocationStatus($data, $lat, $lng);

            $verifiedBarangay = ($lat !== null && $lng !== null)
                ? $this->geoService->identifyVerifiedBarangay($lat, $lng)
                : null;

            $sos = SosAlert::create([
                'reference_number' => $this->generateSosReference(),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'source_type' => $sourceType,
                'user_id' => $reporter?->id,
                'sender_name' => $senderName,
                'contact_number' => $contactNumber,
                'account_status_label' => $accountLabel,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => isset($data['accuracy']) && is_numeric($data['accuracy']) ? (float) $data['accuracy'] : null,
                'location_captured_at' => ($lat !== null && $lng !== null) ? now() : null,
                'location_status' => $locationStatus,
                'barangay_id' => $verifiedBarangay?->id,
                'status' => 'active',
                'call_status' => 'not_called',
                'triggered_at' => ! empty($data['triggered_at']) ? $data['triggered_at'] : now(),
                'escalation_notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
            ]);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $reporter?->id,
                'action_type' => 'created',
                'notes' => sprintf(
                    '%s SOS triggered (%s location).%s',
                    $isCitizen ? 'Citizen' : 'Guest',
                    str_replace('_', ' ', $locationStatus),
                    ! empty($data['is_offline_queued']) ? ' [Synced from offline queue]' : ''
                ),
                'created_at' => now(),
            ]);

            $this->notifyCommandCenterOfSos($sos, 'URGENT CITIZEN SOS ALERT');

            AuditLog::create([
                'user_id' => $reporter?->id,
                'event_type' => 'sos:citizen_triggered',
                'auditable_type' => SosAlert::class,
                'auditable_id' => $sos->id,
                'new_values' => [
                    'reference_number' => $sos->reference_number,
                    'source_type' => $sos->source_type,
                    'location_status' => $sos->location_status,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $sos->load(['barangay', 'user', 'assignments.office']);
        });

        try {
            event(new CitizenSOSCreated($sos));
        } catch (\Throwable) {
            // Fail-safe broadcast
        }

        return $sos;
    }

    /**
     * Trigger a responder distress SOS alert from Personnel.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPersonnelSos(User $personnelUser, array $data): SosAlert
    {
        if (! $personnelUser->isPersonnel()) {
            throw new AccessDeniedHttpException('Only personnel responders may trigger Personnel SOS.');
        }

        if (! empty($data['idempotency_key'])) {
            $existing = SosAlert::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing->load(['barangay', 'personnel', 'office']);
            }
        }

        $sos = DB::transaction(function () use ($personnelUser, $data): SosAlert {
            $profile = $personnelUser->personnelProfile;
            $officeId = $profile?->office_id;
            $currentAssignmentId = isset($data['current_assignment_id'])
                ? (int) $data['current_assignment_id']
                : null;

            if ($currentAssignmentId !== null) {
                $ownsActiveAssignment = $profile !== null && IncidentAssignment::query()
                    ->whereKey($currentAssignmentId)
                    ->where('personnel_id', $profile->id)
                    ->where('status', 'active')
                    ->exists();

                if (! $ownsActiveAssignment) {
                    throw new AccessDeniedHttpException('The selected assignment is not active for this responder.');
                }
            }

            $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null;
            $lng = isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null;

            $latestLocation = $profile?->latestLocation;
            $locationStatus = $this->resolveLocationStatus($data, $lat, $lng);
            $lastKnownLat = $latestLocation?->latitude !== null ? (float) $latestLocation->latitude : null;
            $lastKnownLng = $latestLocation?->longitude !== null ? (float) $latestLocation->longitude : null;

            if ($lat === null && $lng === null && $lastKnownLat !== null && $lastKnownLng !== null) {
                $lat = $lastKnownLat;
                $lng = $lastKnownLng;
                $locationStatus = 'last_known_stale';
            }

            $verifiedBarangay = ($lat !== null && $lng !== null)
                ? $this->geoService->identifyVerifiedBarangay($lat, $lng)
                : null;

            $sos = SosAlert::create([
                'reference_number' => $this->generateSosReference(),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'source_type' => 'personnel',
                'user_id' => $personnelUser->id,
                'personnel_id' => $profile?->id,
                'office_id' => $officeId,
                'current_assignment_id' => $currentAssignmentId,
                'position_role' => $profile?->rank_designation ?? 'Field Responder',
                'duty_status' => $profile?->availability?->operational_status ?? 'active',
                'sender_name' => $personnelUser->name,
                'contact_number' => $personnelUser->phone_number ?? 'Not provided',
                'account_status_label' => 'Field Personnel Responder',
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => isset($data['accuracy']) && is_numeric($data['accuracy']) ? (float) $data['accuracy'] : null,
                'location_captured_at' => ($lat !== null && $lng !== null) ? now() : null,
                'location_status' => $locationStatus,
                'last_known_latitude' => $lastKnownLat,
                'last_known_longitude' => $lastKnownLng,
                'last_known_at' => $latestLocation?->recorded_at,
                'barangay_id' => $verifiedBarangay?->id,
                'status' => 'active',
                'call_status' => 'not_called',
                'triggered_at' => ! empty($data['triggered_at']) ? $data['triggered_at'] : now(),
                'escalation_notes' => isset($data['notes']) ? trim((string) $data['notes']) : 'Responder distress SOS triggered in the field.',
            ]);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $personnelUser->id,
                'action_type' => 'created',
                'notes' => sprintf(
                    'Responder %s (%s) triggered Personnel SOS (%s location).',
                    $personnelUser->name,
                    $profile?->badge_number ?? 'No badge',
                    str_replace('_', ' ', $locationStatus)
                ),
                'created_at' => now(),
            ]);

            $this->notifyCommandCenterOfSos($sos, 'CRITICAL RESPONDER DISTRESS SOS');

            if ($officeId !== null) {
                $officeAdmins = OfficeUser::where('office_id', $officeId)
                    ->where('office_level', 'office_admin')
                    ->pluck('user_id');

                foreach ($officeAdmins as $officeUserId) {
                    Notification::create([
                        'user_id' => $officeUserId,
                        'title' => 'Personnel Distress SOS Alert',
                        'body' => "Responder {$personnelUser->name} triggered a distress SOS alert ({$sos->reference_number}).",
                        'type' => 'personnel_sos_alert',
                        'action_url' => route('office.map'),
                        'is_read' => false,
                        'created_at' => now(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $personnelUser->id,
                'event_type' => 'sos:personnel_triggered',
                'auditable_type' => SosAlert::class,
                'auditable_id' => $sos->id,
                'new_values' => [
                    'reference_number' => $sos->reference_number,
                    'personnel_id' => $profile?->id,
                    'office_id' => $officeId,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $sos->load(['barangay', 'personnel', 'office']);
        });

        try {
            event(new PersonnelSOSCreated($sos));
        } catch (\Throwable) {
            // Fail-safe broadcast
        }

        return $sos;
    }

    /**
     * Acknowledge an active SOS alert by Admin or Super Admin.
     */
    public function acknowledgeSos(User $actor, SosAlert $sos, ?string $notes = null): SosAlert
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may acknowledge SOS alerts.');
        }

        $updated = DB::transaction(function () use ($actor, $sos, $notes): SosAlert {
            $oldStatus = $sos->status;

            $sos->update([
                'status' => in_array($sos->status, ['resolved', 'converted_to_incident', 'cancelled'], true) ? $sos->status : 'acknowledged',
                'acknowledged_by' => $sos->acknowledged_by ?? $actor->id,
                'acknowledged_at' => $sos->acknowledged_at ?? now(),
                'escalation_notes' => $notes ? trim($notes) : $sos->escalation_notes,
            ]);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $actor->id,
                'action_type' => 'acknowledged',
                'notes' => "SOS acknowledged by {$actor->name} (previous status: {$oldStatus}).".($notes ? " Notes: {$notes}" : ''),
                'created_at' => now(),
            ]);

            if ($sos->user_id) {
                Notification::create([
                    'user_id' => $sos->user_id,
                    'title' => 'SOS Alert Acknowledged',
                    'body' => "DIAL DAPH Command Center acknowledged your SOS {$sos->reference_number}. Help is being coordinated.",
                    'type' => 'sos_acknowledged',
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            return $sos->fresh(['barangay', 'user', 'personnel', 'office', 'assignments']);
        });

        try {
            event(new SOSAcknowledged($updated));
        } catch (\Throwable) {
            // Fail-safe
        }

        return $updated;
    }

    /**
     * Record a Command Center call attempt or verification note on an SOS alert.
     */
    public function recordCallAttempt(User $actor, SosAlert $sos, string $outcome, ?string $notes = null): SosAlert
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Unauthorized SOS action.');
        }

        return DB::transaction(function () use ($actor, $sos, $outcome, $notes): SosAlert {
            $update = ['call_status' => $outcome];
            if ($sos->acknowledged_at === null) {
                $update['status'] = 'acknowledged';
                $update['acknowledged_by'] = $actor->id;
                $update['acknowledged_at'] = now();
            }
            $sos->update($update);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $actor->id,
                'action_type' => 'call_attempt',
                'call_outcome' => $outcome,
                'notes' => sprintf(
                    'Call attempt to %s recorded outcome "%s". %s',
                    $sos->contact_number ?: 'no phone on record',
                    $outcome,
                    $notes ? "Notes: {$notes}" : ''
                ),
                'created_at' => now(),
            ]);

            return $sos->fresh(['logs.actor']);
        });
    }

    /**
     * Assign SOS responders strictly from active configured SOS routing rules or explicit admin selection.
     * Never invents unconfigured dispatches.
     *
     * @param  array<string, mixed>  $data
     * @return array{assignment: ?SosAssignment, warning: ?string}
     */
    public function assignSosResponders(User $actor, SosAlert $sos, array $data): array
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may dispatch SOS units.');
        }

        return DB::transaction(function () use ($actor, $sos, $data): array {
            $officeId = ! empty($data['office_id']) ? (int) $data['office_id'] : null;
            $teamId = ! empty($data['team_id']) ? (int) $data['team_id'] : null;
            $personnelId = ! empty($data['personnel_id']) ? (int) $data['personnel_id'] : null;
            $assignmentType = 'manual';

            if (! empty($data['use_routing_rule'])) {
                $ruleSource = $sos->source_type === 'personnel' ? 'personnel' : 'citizen';
                $rule = SosRoutingRule::whereIn('source_type', [$ruleSource, 'all'])
                    ->where('is_active', true)
                    ->orderByRaw('source_type = ? DESC', [$ruleSource])
                    ->orderBy('priority_order')
                    ->first();

                if (! $rule) {
                    $warning = 'No active SOS routing rule is configured for this SOS type. Select an eligible office manually or ask Super Admin to configure SOS routing rules.';
                    SosLog::create([
                        'sos_alert_id' => $sos->id,
                        'actor_id' => $actor->id,
                        'action_type' => 'escalated',
                        'notes' => $warning,
                        'created_at' => now(),
                    ]);

                    return ['assignment' => null, 'warning' => $warning];
                }

                $officeId = $rule->office_id;
                $teamId = $rule->team_id;
                $assignmentType = 'automatic_rule';
            }

            if ($officeId === null) {
                throw new InvalidArgumentException('An authorized responding office must be selected for SOS assignment.');
            }

            if ($personnelId !== null) {
                $responder = PersonnelProfile::findOrFail($personnelId);
                if ((int) $responder->office_id !== $officeId) {
                    throw new InvalidArgumentException('Selected responder does not belong to the chosen office.');
                }
            }

            $assignment = SosAssignment::create([
                'sos_alert_id' => $sos->id,
                'office_id' => $officeId,
                'team_id' => $teamId,
                'personnel_id' => $personnelId,
                'assigned_by' => $actor->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
            ]);

            $sos->update([
                'status' => 'responding',
                'acknowledged_by' => $sos->acknowledged_by ?? $actor->id,
                'acknowledged_at' => $sos->acknowledged_at ?? now(),
            ]);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $actor->id,
                'action_type' => 'assigned',
                'notes' => "Response dispatched via {$assignmentType} to Office #{$officeId}"
                    .($personnelId ? " (Personnel #{$personnelId})." : '.'),
                'created_at' => now(),
            ]);

            $officeAdmins = OfficeUser::where('office_id', $officeId)->pluck('user_id');
            foreach ($officeAdmins as $uid) {
                Notification::create([
                    'user_id' => $uid,
                    'title' => 'SOS Response Assignment',
                    'body' => "Your office has been dispatched to emergency SOS {$sos->reference_number} ({$sos->sender_name}).",
                    'type' => 'sos_dispatched',
                    'action_url' => route('office.map'),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            if ($assignment->personnel?->user_id) {
                Notification::create([
                    'user_id' => $assignment->personnel->user_id,
                    'title' => 'URGENT SOS Dispatch Assignment',
                    'body' => "Respond immediately to SOS {$sos->reference_number} from {$sos->sender_name}.",
                    'type' => 'sos_dispatched',
                    'action_url' => route('personnel.dashboard'),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            try {
                event(new SOSResponseAssigned($assignment->load(['sosAlert', 'office', 'personnel'])));
            } catch (\Throwable) {
                // Fail-safe
            }

            return ['assignment' => $assignment->load(['office', 'team', 'personnel.user']), 'warning' => null];
        });
    }

    /**
     * Escalate an SOS alert for immediate Super Admin / multi-agency attention.
     */
    public function escalateSos(User $actor, SosAlert $sos, string $reason): SosAlert
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Unauthorized SOS escalation.');
        }

        return DB::transaction(function () use ($actor, $sos, $reason): SosAlert {
            $sos->update([
                'status' => 'escalated',
                'escalation_notes' => trim($reason),
            ]);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $actor->id,
                'action_type' => 'escalated',
                'notes' => "SOS escalated by {$actor->name}: {$reason}",
                'created_at' => now(),
            ]);

            foreach (User::where('account_type', 'super_admin')->where('is_active', true)->pluck('id') as $superId) {
                Notification::create([
                    'user_id' => $superId,
                    'title' => 'SOS Alert Escalated',
                    'body' => "SOS {$sos->reference_number} escalated by {$actor->name}: {$reason}",
                    'type' => 'sos_escalated',
                    'action_url' => route('super-admin.sos.index'),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            return $sos->fresh(['logs.actor']);
        });
    }

    /**
     * Resolve or close an SOS alert with verified resolution notes.
     */
    public function resolveSos(User $actor, SosAlert $sos, string $status, string $notes): SosAlert
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Unauthorized SOS resolution.');
        }

        if (! in_array($status, ['resolved', 'closed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Invalid SOS resolution status.');
        }

        $targetStatus = $status === 'closed' ? 'resolved' : $status;

        return DB::transaction(function () use ($actor, $sos, $targetStatus, $notes): SosAlert {
            $sos->update([
                'status' => $targetStatus,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'escalation_notes' => trim($notes),
            ]);

            SosAssignment::where('sos_alert_id', $sos->id)
                ->whereIn('status', ['assigned', 'acknowledged', 'en_route', 'on_scene'])
                ->update(['status' => 'completed']);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $actor->id,
                'action_type' => 'resolved',
                'notes' => "SOS marked {$targetStatus} by {$actor->name}: {$notes}",
                'created_at' => now(),
            ]);

            return $sos->fresh(['assignments', 'logs.actor']);
        });
    }

    /**
     * Explicitly convert an SOS alert into a formal Incident record ONLY when triggered by Admin/Super Admin.
     */
    public function convertSosToIncident(User $actor, SosAlert $sos): Incident
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may convert SOS alerts to incidents.');
        }

        if ($sos->converted_incident_id) {
            return Incident::findOrFail($sos->converted_incident_id);
        }

        return DB::transaction(function () use ($actor, $sos): Incident {
            $reference = 'INC-'.date('Y').'-'.strtoupper(Str::random(6));
            while (Incident::where('reference_number', $reference)->exists()) {
                $reference = 'INC-'.date('Y').'-'.strtoupper(Str::random(6));
            }

            $incident = Incident::create([
                'reference_number' => $reference,
                'reporter_id' => $sos->user_id,
                'is_guest_report' => $sos->source_type === 'guest_citizen',
                'source_channel' => 'admin_encoded',
                'category_id' => null,
                'priority' => 'critical',
                'status' => 'accepted',
                'workflow_stage' => 'assessment_in_progress',
                'assessment_status' => 'in_progress',
                'processed_by_admin_id' => $actor->id,
                'locked_for_admin_at' => now(),
                'description' => "Converted from SOS {$sos->reference_number}. ".($sos->escalation_notes ?? ''),
                'caller_name' => $sos->sender_name,
                'caller_phone' => $sos->contact_number ?: 'Not provided',
                'barangay_id' => $sos->barangay_id,
                'submitted_at' => $sos->triggered_at ?? now(),
                'accepted_at' => now(),
                'version_lock' => 1,
            ]);

            if ($sos->latitude !== null && $sos->longitude !== null) {
                IncidentLocation::create([
                    'incident_id' => $incident->id,
                    'latitude' => $sos->latitude,
                    'longitude' => $sos->longitude,
                    'accuracy' => $sos->accuracy,
                    'landmark' => "Converted from SOS {$sos->reference_number}",
                    'location_source' => 'gps_device',
                ]);
            }

            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'previous_status' => 'submitted',
                'new_status' => 'accepted',
                'changed_by' => $actor->id,
                'change_reason' => "Converted from SOS alert {$sos->reference_number} by {$actor->name}.",
                'created_at' => now(),
            ]);

            $sos->update([
                'converted_incident_id' => $incident->id,
                'status' => 'converted_to_incident',
            ]);

            SosLog::create([
                'sos_alert_id' => $sos->id,
                'actor_id' => $actor->id,
                'action_type' => 'converted',
                'notes' => "SOS converted into Incident {$incident->reference_number} by {$actor->name}.",
                'created_at' => now(),
            ]);

            return $incident;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveLocationStatus(array $data, ?float $lat, ?float $lng): string
    {
        if ($lat === null || $lng === null) {
            return 'unavailable';
        }

        if (($data['location_status'] ?? null) === 'last_known_stale') {
            return 'last_known_stale';
        }

        if (($data['location_status'] ?? null) === 'manual_pin') {
            return 'manual_pin';
        }

        return 'live';
    }

    protected function notifyCommandCenterOfSos(SosAlert $sos, string $headlinePrefix): void
    {
        $commandStaff = User::whereIn('account_type', ['admin', 'super_admin'])
            ->where('is_active', true)
            ->get(['id', 'account_type']);

        foreach ($commandStaff as $staff) {
            $actionUrl = $staff->account_type === 'super_admin'
                ? route('super-admin.sos.index')
                : route('admin.sos.index');

            Notification::create([
                'user_id' => $staff->id,
                'title' => "{$headlinePrefix}: {$sos->reference_number}",
                'body' => "{$sos->sender_name} (".($sos->contact_number ?: 'no phone').') triggered an SOS alert. Immediate attention required.',
                'type' => 'sos_alert',
                'action_url' => $actionUrl,
                'is_read' => false,
                'created_at' => now(),
            ]);
        }
    }

    protected function generateSosReference(): string
    {
        $year = date('Y');
        do {
            $ref = sprintf('SOS-%s-%s', $year, strtoupper(Str::random(6)));
        } while (SosAlert::where('reference_number', $ref)->exists());

        return $ref;
    }
}
