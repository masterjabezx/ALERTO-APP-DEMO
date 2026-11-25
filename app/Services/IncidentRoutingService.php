<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentRoutingRule;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IncidentRoutingService
{
    /**
     * Compute automatic routing recommendation based on rules, geography, and live availability.
     *
     * @return array<string, mixed>
     */
    public function recommendRouting(Incident $incident): array
    {
        // 1. Evaluate Routing Rules (Barangay-specific first, then Category default)
        $rule = IncidentRoutingRule::with(['targetOffice.teams', 'fallbackOffice.teams'])
            ->where('category_id', $incident->category_id)
            ->where('is_active', true)
            ->where(function ($q) use ($incident) {
                $q->where('barangay_id', $incident->barangay_id)
                    ->orWhereNull('barangay_id');
            })
            ->orderByRaw('barangay_id IS NOT NULL DESC')
            ->first();

        $targetOffice = $rule?->targetOffice;
        $reason = '';
        $ruleMatched = false;

        if ($targetOffice) {
            $ruleMatched = true;
            $categoryName = $incident->category?->name ?? 'Category';
            $barangayName = $incident->barangay?->name ?? 'Municipality';
            $reason = "Rule matched for {$categoryName} in {$barangayName} -> Designated response agency: {$targetOffice->name}.";
        } else {
            // Fallback: match office responsibility by department or CDRRMO
            $targetOffice = Office::where('department_type', 'disaster')->first() ?? Office::first();
            $reason = "No specific category rule found. Recommended lead disaster coordination agency: {$targetOffice?->name}.";
        }

        // 2. Determine best response team for office
        $recommendedTeam = null;
        if ($targetOffice) {
            $recommendedTeam = $targetOffice->teams()->first();
        }

        // 3. Find eligible personnel in target office / team
        $allPersonnelQuery = PersonnelProfile::with(['user', 'availability'])
            ->where('office_id', $targetOffice?->id);

        if ($recommendedTeam) {
            $teamPersonnel = (clone $allPersonnelQuery)->whereHas('teams', function ($q) use ($recommendedTeam) {
                $q->where('response_teams.id', $recommendedTeam->id);
            })->get();

            $allPersonnel = $teamPersonnel->isNotEmpty() ? $teamPersonnel : $allPersonnelQuery->get();
        } else {
            $allPersonnel = $allPersonnelQuery->get();
        }

        // 4. Separate available vs. unavailable personnel
        // Only personnel with operational_status === 'available' are recommended!
        $eligiblePersonnel = $allPersonnel->filter(function ($personnel) {
            return $personnel->availability?->operational_status === 'available';
        })->values();

        $offlinePersonnel = $allPersonnel->filter(function ($personnel) {
            return $personnel->availability?->operational_status !== 'available';
        })->values();

        // Check active incident load on available personnel
        foreach ($eligiblePersonnel as $p) {
            $p->active_incidents_count = IncidentAssignment::where('personnel_id', $p->id)
                ->where('status', 'active')
                ->count();
        }

        $hasOfflineWarning = $eligiblePersonnel->isEmpty() && $allPersonnel->isNotEmpty();
        if ($hasOfflineWarning) {
            $reason .= ' WARNING: All qualified departmental responders are currently OFFLINE / OFF-DUTY. Manual assignment or mutual aid escalation required.';
        }

        return [
            'recommended_office' => $targetOffice,
            'recommended_team' => $recommendedTeam,
            'eligible_personnel' => $eligiblePersonnel,
            'offline_personnel' => $offlinePersonnel,
            'rule_matched' => $ruleMatched,
            'recommendation_reason' => $reason,
            'has_offline_warning' => $hasOfflineWarning,
            'fallback_office' => $rule?->fallbackOffice,
        ];
    }

    /**
     * Assign incident to office, team, and/or personnel.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    public function assignIncident(User $dispatcher, Incident $incident, array $data): IncidentAssignment
    {
        // Must be in accepted or higher active status
        if ($incident->status !== 'accepted' && ! in_array($incident->status, ['dispatched', 'acknowledged', 'in_progress'], true)) {
            throw new InvalidArgumentException("Incident cannot be assigned in status: {$incident->status}. Incident must first be reviewed and ACCEPTED.");
        }

        $officeId = (int) $data['office_id'];
        $teamId = ! empty($data['team_id']) ? (int) $data['team_id'] : null;
        $personnelId = ! empty($data['personnel_id']) ? (int) $data['personnel_id'] : null;
        $personnelIds = $data['personnel_ids'] ?? [];

        return DB::transaction(function () use ($dispatcher, $incident, $officeId, $teamId, $personnelId, $personnelIds, $data) {
            // Prevent duplicate identical active assignment
            $existing = IncidentAssignment::where('incident_id', $incident->id)
                ->where('office_id', $officeId)
                ->where('status', 'active')
                ->when($personnelId, fn ($q) => $q->where('personnel_id', $personnelId))
                ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('An active assignment already exists for this unit on this incident.');
            }

            // Create primary assignment
            $primaryAssignment = IncidentAssignment::create([
                'incident_id' => $incident->id,
                'office_id' => $officeId,
                'team_id' => $teamId,
                'personnel_id' => $personnelId,
                'assigned_by' => $dispatcher->id,
                'assignment_type' => $data['assignment_type'] ?? 'manual',
                'status' => 'active',
                'assigned_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // If multiple personnel were selected, create assignments for each
            if (! empty($personnelIds) && is_array($personnelIds)) {
                foreach ($personnelIds as $extraPersonnelId) {
                    if ((int) $extraPersonnelId !== $personnelId) {
                        IncidentAssignment::firstOrCreate(
                            [
                                'incident_id' => $incident->id,
                                'office_id' => $officeId,
                                'personnel_id' => (int) $extraPersonnelId,
                                'status' => 'active',
                            ],
                            [
                                'team_id' => $teamId,
                                'assigned_by' => $dispatcher->id,
                                'assignment_type' => 'manual',
                                'assigned_at' => now(),
                                'notes' => $data['notes'] ?? null,
                            ]
                        );
                    }
                }
            }

            // Audit log
            AuditLog::create([
                'user_id' => $dispatcher->id,
                'event_type' => 'incident:assigned',
                'auditable_type' => IncidentAssignment::class,
                'auditable_id' => $primaryAssignment->id,
                'new_values' => [
                    'incident_id' => $incident->id,
                    'office_id' => $officeId,
                    'team_id' => $teamId,
                    'personnel_id' => $personnelId,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $primaryAssignment;
        });
    }

    /**
     * Authorize official emergency dispatch.
     *
     * @throws InvalidArgumentException
     */
    public function dispatchIncident(User $dispatcher, Incident $incident, ?string $dispatchNotes = null): Incident
    {
        // 1. CANNOT dispatch before acceptance
        if ($incident->status !== 'accepted') {
            throw new InvalidArgumentException("Incident cannot be dispatched from status '{$incident->status}'. Incident must be ACCEPTED first.");
        }

        return DB::transaction(function () use ($dispatcher, $incident, $dispatchNotes) {
            $lockedIncident = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            // 2. CANNOT dispatch without confirmed assignment
            $activeAssignments = IncidentAssignment::with(['personnel.user', 'office.users'])
                ->where('incident_id', $lockedIncident->id)
                ->where('status', 'active')
                ->get();

            if ($activeAssignments->isEmpty()) {
                throw new InvalidArgumentException('Official dispatch requires at least one confirmed response team or office assignment.');
            }

            $previousStatus = $lockedIncident->status;
            $lockedIncident->status = 'dispatched';
            $lockedIncident->workflow_stage = 'dispatched';
            $lockedIncident->dispatched_at = $lockedIncident->dispatched_at ?? now();
            $lockedIncident->has_assignment_warning = false;
            $lockedIncident->assignment_warning_message = null;
            $lockedIncident->version_lock++;
            $lockedIncident->save();

            // Record status transition
            IncidentStatusHistory::create([
                'incident_id' => $lockedIncident->id,
                'previous_status' => $previousStatus,
                'new_status' => 'dispatched',
                'changed_by' => $dispatcher->id,
                'change_reason' => $dispatchNotes ?? 'Official dispatch authorized by EOC Command. Responders mobilized.',
                'created_at' => now(),
            ]);

            // 3. Send dispatch notifications ONLY to confirmed assigned personnel
            $notifiedUserIds = [];

            foreach ($activeAssignments as $assignment) {
                // If specific personnel assigned
                if ($assignment->personnel && $assignment->personnel->user_id) {
                    $userId = $assignment->personnel->user_id;
                    if (! in_array($userId, $notifiedUserIds, true)) {
                        Notification::create([
                            'user_id' => $userId,
                            'title' => 'EMERGENCY DISPATCH ORDER',
                            'body' => "You have been dispatched to {$lockedIncident->category?->name} ({$lockedIncident->reference_number}) at {$lockedIncident->barangay?->name}.",
                            'type' => 'incident_dispatch_order',
                            'action_url' => route('personnel.dashboard'),
                            'is_read' => false,
                            'created_at' => now(),
                        ]);
                        $notifiedUserIds[] = $userId;
                    }
                }

                // Also notify Office Dispatchers for assigned office
                if ($assignment->office) {
                    $officeDispatchers = $assignment->office->users()
                        ->wherePivot('can_dispatch_units', true)
                        ->get();

                    foreach ($officeDispatchers as $officeUser) {
                        if (! in_array($officeUser->id, $notifiedUserIds, true)) {
                            Notification::create([
                                'user_id' => $officeUser->id,
                                'title' => 'Incident Dispatched to Office',
                                'body' => "Incident {$lockedIncident->reference_number} dispatched to {$assignment->office->name}.",
                                'type' => 'office_incident_dispatched',
                                'action_url' => route('office.dashboard'),
                                'is_read' => false,
                                'created_at' => now(),
                            ]);
                            $notifiedUserIds[] = $officeUser->id;
                        }
                    }
                }
            }

            // 4. Notify reporting citizen
            if ($lockedIncident->reporter_id) {
                Notification::create([
                    'user_id' => $lockedIncident->reporter_id,
                    'title' => 'Responders Dispatched',
                    'body' => "Emergency response teams have been officially dispatched to your reported incident {$lockedIncident->reference_number}.",
                    'type' => 'incident_dispatched',
                    'action_url' => route('citizen.records.show', $lockedIncident->reference_number),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $dispatcher->id,
                'event_type' => 'incident:dispatched',
                'auditable_type' => Incident::class,
                'auditable_id' => $lockedIncident->id,
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => 'dispatched', 'notified_users_count' => count($notifiedUserIds)],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $lockedIncident;
        });
    }

    /**
     * Advance incident operational milestone status (Acknowledged, On Scene, In Progress, Resolved).
     *
     * @throws InvalidArgumentException
     */
    public function advanceMilestoneStatus(User $user, Incident $incident, string $newStatus, ?string $remarks = null): Incident
    {
        $allowedTransitions = [
            'dispatched' => ['acknowledged', 'on_the_way'],
            'acknowledged' => ['on_the_way', 'on_scene'],
            'on_the_way' => ['on_scene', 'in_progress'],
            'on_scene' => ['in_progress', 'resolved'],
            'in_progress' => ['resolved', 'closed'],
            'resolved' => ['closed'],
        ];

        $currentStatus = $incident->status;

        // Verify transition validity
        if (! isset($allowedTransitions[$currentStatus]) || ! in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
            // Admins can advance with justification
            if (! $user->isAdmin()) {
                throw new InvalidArgumentException("Cannot transition incident from '{$currentStatus}' to '{$newStatus}'.");
            }
        }

        return DB::transaction(function () use ($user, $incident, $newStatus, $remarks) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $locked->status;

            // Multi-responder guard: if field personnel marks resolved, complete only their assignment first.
            // Do not mark parent incident resolved while other active unit assignments remain ongoing.
            $effectiveStatus = $newStatus;
            $effectiveRemarks = $remarks;

            if ($user->isPersonnel() && $newStatus === 'resolved') {
                $personnel = $user->personnelProfile;
                if ($personnel) {
                    IncidentAssignment::where('incident_id', $locked->id)
                        ->where('personnel_id', $personnel->id)
                        ->where('status', 'active')
                        ->update(['status' => 'completed']);
                }

                $otherActiveCount = IncidentAssignment::where('incident_id', $locked->id)
                    ->where('status', 'active')
                    ->count();

                if ($otherActiveCount > 0) {
                    $effectiveStatus = 'in_progress';
                    $effectiveRemarks = trim(($remarks ? $remarks.' ' : '')."[Unit {$user->name} completed field task; {$otherActiveCount} other active unit(s) remaining.]");
                }
            } elseif ($newStatus === 'closed') {
                IncidentAssignment::where('incident_id', $locked->id)
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);
            }

            $locked->status = $effectiveStatus;
            $locked->workflow_stage = match ($effectiveStatus) {
                'acknowledged', 'on_the_way', 'on_scene', 'in_progress' => 'active_response',
                'resolved' => 'resolved',
                'closed' => 'closed',
                default => $locked->workflow_stage,
            };

            if ($effectiveStatus === 'resolved') {
                $locked->resolved_at = now();
            }
            if ($effectiveStatus === 'closed') {
                $locked->closed_at = now();
            }
            $locked->version_lock++;
            $locked->save();

            // Save history
            IncidentStatusHistory::create([
                'incident_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => $effectiveStatus,
                'changed_by' => $user->id,
                'change_reason' => $effectiveRemarks ?? "Incident status updated to {$effectiveStatus}.",
                'created_at' => now(),
            ]);

            // Notify citizen
            if ($locked->reporter_id) {
                $statusHuman = strtoupper(str_replace('_', ' ', $effectiveStatus));
                Notification::create([
                    'user_id' => $locked->reporter_id,
                    'title' => "Incident Status: {$statusHuman}",
                    'body' => "Your reported incident {$locked->reference_number} is now marked as {$statusHuman}. {$effectiveRemarks}",
                    'type' => "incident_status_{$effectiveStatus}",
                    'action_url' => route('citizen.records.show', $locked->reference_number),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => "incident:status_{$effectiveStatus}",
                'auditable_type' => Incident::class,
                'auditable_id' => $locked->id,
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => $effectiveStatus, 'remarks' => $effectiveRemarks],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $locked;
        });
    }
}
