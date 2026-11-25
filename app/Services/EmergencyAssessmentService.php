<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\EmergencyAssessmentUpdated;
use App\Events\EmergencyCallAttempted;
use App\Events\EmergencyIncidentActivated;
use App\Events\EmergencyReportViewed;
use App\Events\EmergencyResponseAssigned;
use App\Events\EmergencyResponseDispatched;
use App\Events\OfficeIncidentNotification;
use App\Events\PersonnelDispatchNotification;
use App\Models\AuditLog;
use App\Models\EmergencyAssessment;
use App\Models\EmergencyCallAttempt;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentRoutingRule;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use App\Models\Office;
use App\Models\OfficeResponsibility;
use App\Models\PersonnelProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmergencyAssessmentService
{
    public function __construct(
        protected GeoService $geoService
    ) {}

    /**
     * Claim/record processing view on an unclassified report for concurrency awareness.
     */
    public function markReportViewed(User $actor, Incident $incident): Incident
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may process emergency reports.');
        }

        if ($incident->processed_by_admin_id === null || $actor->isSuperAdmin()) {
            $incident->update([
                'processed_by_admin_id' => $incident->processed_by_admin_id ?? $actor->id,
                'locked_for_admin_at' => $incident->locked_for_admin_at ?? now(),
            ]);
        }

        $fresh = $incident->fresh(['processedByAdmin', 'location', 'barangay']);

        try {
            event(new EmergencyReportViewed($fresh, $actor));
        } catch (\Throwable) {
            // Fail-safe broadcast
        }

        return $fresh;
    }

    /**
     * Log an initiated call attempt from Command Center toward reporting citizen/caller.
     */
    public function initiateCallAttempt(User $actor, Incident $incident, ?string $phoneNumber = null): EmergencyCallAttempt
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may initiate verification calls.');
        }

        $dialed = trim((string) ($phoneNumber ?: $incident->caller_phone ?: ''));
        if ($dialed === '') {
            throw new InvalidArgumentException('No caller phone number is available on this report.');
        }

        $attempt = DB::transaction(function () use ($actor, $incident, $dialed): EmergencyCallAttempt {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            if ($locked->processed_by_admin_id === null) {
                $locked->processed_by_admin_id = $actor->id;
                $locked->locked_for_admin_at = now();
            }

            if ($locked->workflow_stage === 'new_report' || $locked->workflow_stage === null) {
                $locked->workflow_stage = 'awaiting_call';
            }
            if ($locked->assessment_status === 'unassessed' || $locked->assessment_status === null) {
                $locked->assessment_status = 'call_attempted';
            }
            $locked->save();

            $attempt = EmergencyCallAttempt::create([
                'incident_id' => $locked->id,
                'initiated_by' => $actor->id,
                'phone_number_dialed' => $dialed,
                'call_outcome' => 'initiated',
                'initiated_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'event_type' => 'incident:call_initiated',
                'auditable_type' => Incident::class,
                'auditable_id' => $locked->id,
                'new_values' => [
                    'call_attempt_id' => $attempt->id,
                    'phone_number_dialed' => $dialed,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $attempt->load(['incident', 'initiator']);
        });

        try {
            event(new EmergencyCallAttempted($attempt));
        } catch (\Throwable) {
            // Fail-safe broadcast
        }

        return $attempt;
    }

    /**
     * Record call outcome and manual call verification notes.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordCallOutcome(User $actor, Incident $incident, array $data): EmergencyCallAttempt
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may record call outcomes.');
        }

        $outcome = (string) $data['call_outcome'];
        $allowedOutcomes = ['answered', 'no_answer', 'busy', 'invalid_number', 'callback_required'];
        if (! in_array($outcome, $allowedOutcomes, true)) {
            throw new InvalidArgumentException("Invalid call outcome: {$outcome}");
        }

        $attempt = DB::transaction(function () use ($actor, $incident, $data, $outcome): EmergencyCallAttempt {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            $latestOpen = EmergencyCallAttempt::where('incident_id', $locked->id)
                ->where('call_outcome', 'initiated')
                ->latest('initiated_at')
                ->first();

            $payload = [
                'call_outcome' => $outcome,
                'verification_notes' => isset($data['verification_notes']) ? trim((string) $data['verification_notes']) : null,
                'location_confirmed' => ! empty($data['location_confirmed']),
                'category_confirmed' => ! empty($data['category_confirmed']),
                'callback_required' => $outcome === 'callback_required' || ! empty($data['callback_required']),
                'recorded_at' => now(),
            ];

            if ($latestOpen) {
                $latestOpen->update($payload);
                $attempt = $latestOpen;
            } else {
                $attempt = EmergencyCallAttempt::create(array_merge($payload, [
                    'incident_id' => $locked->id,
                    'initiated_by' => $actor->id,
                    'phone_number_dialed' => trim((string) ($data['phone_number_dialed'] ?? $locked->caller_phone ?? '')),
                    'initiated_at' => now(),
                ]));
            }

            if ($outcome === 'answered') {
                $locked->assessment_status = $locked->assessment_status === 'completed' ? 'completed' : 'in_progress';
                if (in_array($locked->workflow_stage, ['new_report', 'awaiting_call', null], true)) {
                    $locked->workflow_stage = 'assessment_in_progress';
                }
                if (! empty($data['mark_number_verified'])) {
                    $locked->is_mobile_number_verified = true;
                    $locked->contact_verification_status = 'verified';
                }
            } elseif ($outcome === 'invalid_number') {
                $locked->is_mobile_number_verified = false;
                $locked->contact_verification_status = 'failed_verification';
                $locked->workflow_stage = $locked->workflow_stage === 'new_report' ? 'awaiting_call' : $locked->workflow_stage;
            } else {
                $locked->workflow_stage = $locked->workflow_stage === 'new_report' ? 'awaiting_call' : $locked->workflow_stage;
            }

            $locked->save();

            AuditLog::create([
                'user_id' => $actor->id,
                'event_type' => 'incident:call_outcome_recorded',
                'auditable_type' => Incident::class,
                'auditable_id' => $locked->id,
                'new_values' => [
                    'call_attempt_id' => $attempt->id,
                    'call_outcome' => $outcome,
                    'location_confirmed' => $attempt->location_confirmed,
                    'category_confirmed' => $attempt->category_confirmed,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $attempt->load(['incident', 'initiator']);
        });

        try {
            event(new EmergencyCallAttempted($attempt));
        } catch (\Throwable) {
            // Fail-safe broadcast
        }

        return $attempt;
    }

    /**
     * Complete emergency assessment, classify the incident, evaluate active routing rules & offices,
     * and trigger verified dispatch or log explicit assignment warnings without fabricating dispatches.
     *
     * @param  array<string, mixed>  $data
     * @return array{
     *     incident: Incident,
     *     assessment: EmergencyAssessment,
     *     assignments: Collection<int, IncidentAssignment>,
     *     warning: ?string
     * }
     */
    public function confirmAssessmentAndDeploy(User $actor, Incident $incident, array $data): array
    {
        if (! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Only Command Center Admin or Super Admin may confirm emergency assessments and deploy responses.');
        }

        $category = IncidentCategory::findOrFail((int) $data['category_id']);
        $priority = (string) ($data['priority'] ?? $category->default_priority ?? 'high');
        if (! in_array($priority, ['critical', 'high', 'medium', 'low'], true)) {
            throw new InvalidArgumentException("Invalid incident priority: {$priority}");
        }

        $result = DB::transaction(function () use ($actor, $incident, $category, $priority, $data): array {
            $locked = Incident::with(['location', 'assignments'])
                ->where('id', $incident->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'rejected' && ! $actor->isSuperAdmin()) {
                throw new InvalidArgumentException('Rejected incidents can only be re-activated via LDRRMO Super Admin override.');
            }

            $previousStatus = $locked->status;

            // Optionally update adjusted pin / verified barangay if Command Center verified coordinates on map
            $verifiedBarangayId = ! empty($data['verified_barangay_id'])
                ? (int) $data['verified_barangay_id']
                : $locked->barangay_id;

            if (isset($data['latitude'], $data['longitude']) && is_numeric($data['latitude']) && is_numeric($data['longitude'])) {
                $lat = (float) $data['latitude'];
                $lng = (float) $data['longitude'];
                if ($locked->location) {
                    $locked->location->update([
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'landmark' => $data['landmark_details'] ?? $locked->location->landmark,
                    ]);
                }
                if (empty($data['verified_barangay_id'])) {
                    $verifiedBarangay = $this->geoService->identifyVerifiedBarangay($lat, $lng);
                    $verifiedBarangayId = $verifiedBarangay?->id ?? $verifiedBarangayId;
                }
            }

            $countsUnknown = ! empty($data['casualty_counts_unknown']);

            $requiredOfficeIds = collect($data['required_office_ids'] ?? [])
                ->filter(fn (mixed $id): bool => is_numeric($id))
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $requiredTeamIds = collect($data['required_team_ids'] ?? [])
                ->filter(fn (mixed $id): bool => is_numeric($id))
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $requiredQualifications = collect($data['required_qualifications'] ?? [])
                ->filter(fn (mixed $q): bool => is_string($q) && trim($q) !== '')
                ->map(fn (mixed $q): string => trim((string) $q))
                ->unique()
                ->values()
                ->all();

            // Persist full Emergency Assessment record
            $assessment = EmergencyAssessment::updateOrCreate(
                ['incident_id' => $locked->id],
                [
                    'assessed_by' => $actor->id,
                    'category_id' => $category->id,
                    'priority' => $priority,
                    'verified_barangay_id' => $verifiedBarangayId,
                    'incident_description' => trim((string) $data['incident_description']),
                    'people_involved_count' => $countsUnknown ? null : ($this->nullableInt($data['people_involved_count'] ?? null)),
                    'injured_count' => $countsUnknown ? null : ($this->nullableInt($data['injured_count'] ?? null)),
                    'needing_rescue_count' => $countsUnknown ? null : ($this->nullableInt($data['needing_rescue_count'] ?? null)),
                    'fatalities_count' => $countsUnknown ? null : ($this->nullableInt($data['fatalities_count'] ?? null)),
                    'casualty_counts_unknown' => $countsUnknown,
                    'injury_medical_needs' => $this->nullableString($data['injury_medical_needs'] ?? null),
                    'hazards_present' => $this->nullableString($data['hazards_present'] ?? null),
                    'assistance_required' => $this->nullableString($data['assistance_required'] ?? null),
                    'required_equipment' => $this->nullableString($data['required_equipment'] ?? null),
                    'required_office_ids' => $requiredOfficeIds ?: null,
                    'required_team_ids' => $requiredTeamIds ?: null,
                    'required_qualifications' => $requiredQualifications ?: null,
                    'landmark_details' => $this->nullableString($data['landmark_details'] ?? null),
                    'additional_remarks' => $this->nullableString($data['additional_remarks'] ?? null),
                    'location_confirmed' => ! empty($data['location_confirmed']),
                    'category_confirmed' => ! empty($data['category_confirmed']),
                    'confirmed_at' => now(),
                ]
            );

            // Classify Incident and mark Official Active Intake ('accepted')
            $locked->category_id = $category->id;
            $locked->priority = $priority;
            $locked->barangay_id = $verifiedBarangayId;
            $locked->description = trim((string) $data['incident_description']);
            $locked->status = in_array($locked->status, ['dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress'], true)
                ? $locked->status
                : 'accepted';
            $locked->workflow_stage = in_array($locked->workflow_stage, ['dispatched', 'active_response'], true)
                ? $locked->workflow_stage
                : 'classified_ready';
            $locked->assessment_status = 'completed';
            $locked->accepted_at = $locked->accepted_at ?? now();
            $locked->classified_at = $locked->classified_at ?? now();

            if (! empty($data['mark_number_verified'])) {
                $locked->is_mobile_number_verified = true;
                $locked->contact_verification_status = 'verified';
            }

            if ($actor->isSuperAdmin() && $locked->processed_by_admin_id && (int) $locked->processed_by_admin_id !== (int) $actor->id) {
                $locked->overridden_by_super_admin_id = $actor->id;
            }
            $locked->processed_by_admin_id = $locked->processed_by_admin_id ?? $actor->id;
            $locked->version_lock++;

            // Evaluate automated routing rules & office assignments
            $deployment = $this->executeDeploymentPlan(
                actor: $actor,
                incident: $locked,
                category: $category,
                priority: $priority,
                verifiedBarangayId: $verifiedBarangayId,
                requiredOfficeIds: $requiredOfficeIds,
                requiredTeamIds: $requiredTeamIds,
                requiredQualifications: $requiredQualifications,
                manualPersonnelIds: $data['personnel_ids'] ?? [],
                overrideReason: $this->nullableString($data['manual_override_reason'] ?? null),
                assignOfficeOnly: ! empty($data['assign_office_only'])
            );

            if ($deployment['warning'] !== null) {
                $locked->has_assignment_warning = true;
                $locked->assignment_warning_message = mb_substr($deployment['warning'], 0, 495);
            } else {
                $locked->has_assignment_warning = false;
                $locked->assignment_warning_message = null;
            }

            if ($deployment['dispatched'] && ! in_array($locked->status, ['acknowledged', 'on_the_way', 'on_scene', 'in_progress'], true)) {
                $locked->status = 'dispatched';
                $locked->workflow_stage = 'dispatched';
                $locked->dispatched_at = $locked->dispatched_at ?? now();
            }

            $locked->save();

            IncidentStatusHistory::create([
                'incident_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => $locked->status,
                'changed_by' => $actor->id,
                'change_reason' => $deployment['dispatched']
                    ? "Call assessment confirmed ({$category->name}, ".strtoupper($priority).') and emergency response deployed.'
                    : "Emergency assessment confirmed ({$category->name}, ".strtoupper($priority).') awaiting responder assignment.',
                'created_at' => now(),
            ]);

            if ($locked->reporter_id) {
                Notification::create([
                    'user_id' => $locked->reporter_id,
                    'title' => $deployment['dispatched'] ? 'Emergency Response Dispatched' : 'Emergency Report Verified',
                    'body' => $deployment['dispatched']
                        ? "Report {$locked->reference_number} has been verified as {$category->name}. Responders have been dispatched."
                        : "Report {$locked->reference_number} has been verified as {$category->name} by DIAL DAPH Command Center.",
                    'type' => $deployment['dispatched'] ? 'incident_dispatched' : 'incident_accepted',
                    'action_url' => route('citizen.records.show', $locked->reference_number),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'event_type' => 'incident:assessment_confirmed',
                'auditable_type' => Incident::class,
                'auditable_id' => $locked->id,
                'old_values' => ['status' => $previousStatus],
                'new_values' => [
                    'category_id' => $category->id,
                    'priority' => $priority,
                    'status' => $locked->status,
                    'workflow_stage' => $locked->workflow_stage,
                    'assignments_created' => $deployment['assignments']->count(),
                    'warning' => $deployment['warning'],
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return [
                'incident' => $locked->fresh(['category', 'barangay', 'location', 'assessment', 'assignments.office', 'assignments.personnel.user', 'callAttempts']),
                'assessment' => $assessment->fresh(['category', 'verifiedBarangay', 'assessor']),
                'assignments' => $deployment['assignments'],
                'warning' => $deployment['warning'],
                'dispatched' => $deployment['dispatched'],
            ];
        });

        try {
            event(new EmergencyAssessmentUpdated($result['assessment']));
            event(new EmergencyIncidentActivated($result['incident']));

            foreach ($result['assignments'] as $assignment) {
                event(new EmergencyResponseAssigned($assignment));
                if ($assignment->office_id) {
                    event(new OfficeIncidentNotification($result['incident'], (int) $assignment->office_id));
                }
                if ($assignment->personnel?->user_id) {
                    event(new PersonnelDispatchNotification($result['incident'], (int) $assignment->personnel->user_id));
                }
            }

            if ($result['dispatched']) {
                event(new EmergencyResponseDispatched($result['incident']));
            }
        } catch (\Throwable) {
            // Fail-safe broadcast
        }

        return [
            'incident' => $result['incident'],
            'assessment' => $result['assessment'],
            'assignments' => $result['assignments'],
            'warning' => $result['warning'],
        ];
    }

    /**
     * Evaluate routing rules, authorized offices/barangays, teams, qualifications, and personnel availability.
     *
     * @param  array<int>  $requiredOfficeIds
     * @param  array<int>  $requiredTeamIds
     * @param  array<string>  $requiredQualifications
     * @param  array<mixed>  $manualPersonnelIds
     * @return array{assignments: Collection<int, IncidentAssignment>, warning: ?string, dispatched: bool}
     */
    protected function executeDeploymentPlan(
        User $actor,
        Incident $incident,
        IncidentCategory $category,
        string $priority,
        ?int $verifiedBarangayId,
        array $requiredOfficeIds,
        array $requiredTeamIds,
        array $requiredQualifications,
        array $manualPersonnelIds,
        ?string $overrideReason,
        bool $assignOfficeOnly
    ): array {
        // 1. Evaluate matching IncidentRoutingRule
        $matchingRule = IncidentRoutingRule::with(['targetOffice', 'fallbackOffice', 'responseTeam'])
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->where(function ($q) use ($verifiedBarangayId): void {
                $q->where('barangay_id', $verifiedBarangayId)
                    ->orWhereNull('barangay_id');
            })
            ->where(function ($q) use ($priority): void {
                $q->where('priority', $priority)
                    ->orWhereNull('priority');
            })
            ->orderByRaw('barangay_id IS NOT NULL DESC')
            ->orderByRaw('priority IS NOT NULL DESC')
            ->first();

        $officeIds = collect($requiredOfficeIds);
        $ruleRequiredCount = (int) ($matchingRule?->required_responders_count ?? 1);
        $ruleAutoDispatch = (bool) ($matchingRule?->auto_dispatch ?? true);

        if ($matchingRule) {
            $officeIds->push((int) $matchingRule->target_office_id);
            foreach ($matchingRule->support_office_ids ?? [] as $supportId) {
                if (is_numeric($supportId)) {
                    $officeIds->push((int) $supportId);
                }
            }
            if ($matchingRule->response_team_id) {
                $requiredTeamIds[] = (int) $matchingRule->response_team_id;
            }
            if (! empty($matchingRule->required_qualifications)) {
                $requiredQualifications = array_values(array_unique(array_merge($requiredQualifications, $matchingRule->required_qualifications)));
            }
        }

        // Also check OfficeResponsibility mappings when no explicit office selection & no rule matched
        if ($officeIds->isEmpty()) {
            $respOfficeIds = OfficeResponsibility::where('category_slug', $category->slug)
                ->pluck('office_id');
            $officeIds = $officeIds->merge($respOfficeIds);
        }

        $officeIds = $officeIds->filter()->unique()->values();

        if ($officeIds->isEmpty()) {
            $warning = "No active routing rule or authorized office responsibility matches {$category->name}. Add/select an office or configure routing rules before dispatch.";
            $this->notifyAdminsOfDeploymentWarning($incident, $warning);

            return [
                'assignments' => collect(),
                'warning' => $warning,
                'dispatched' => false,
            ];
        }

        // Filter offices by active state and verified barangay authorization (if office defines barangay restrictions)
        $eligibleOffices = Office::with(['teams', 'authorizedBarangays'])
            ->whereIn('id', $officeIds->all())
            ->where('is_active', true)
            ->get()
            ->filter(function (Office $office) use ($verifiedBarangayId): bool {
                if ($verifiedBarangayId === null) {
                    return true;
                }
                if ($office->authorizedBarangays->isEmpty()) {
                    return true;
                }

                return $office->authorizedBarangays->contains('id', $verifiedBarangayId);
            });

        // Fall back to rule fallback office if primary office excluded or inactive
        if ($eligibleOffices->isEmpty() && $matchingRule?->fallbackOffice?->is_active) {
            $eligibleOffices = collect([$matchingRule->fallbackOffice->load(['teams', 'authorizedBarangays'])]);
        }

        if ($eligibleOffices->isEmpty()) {
            $warning = 'Target office(s) are inactive or not authorized for the incident barangay. Provide a manual office override reason to dispatch.';
            $this->notifyAdminsOfDeploymentWarning($incident, $warning);

            return [
                'assignments' => collect(),
                'warning' => $warning,
                'dispatched' => false,
            ];
        }

        $createdAssignments = collect();
        $warnings = [];
        $assignmentType = $matchingRule ? 'automatic_rule' : 'manual';

        $normalizedManualPersonnelIds = collect($manualPersonnelIds)
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        foreach ($eligibleOffices as $office) {
            $teamId = collect($requiredTeamIds)
                ->first(fn (int $tid): bool => $office->teams->contains('id', $tid));

            // Select manual personnel belonging to this office, OR auto-select available qualified personnel
            $officeManualPersonnel = $normalizedManualPersonnelIds->isNotEmpty()
                ? PersonnelProfile::with(['user', 'availability'])
                    ->where('office_id', $office->id)
                    ->whereIn('id', $normalizedManualPersonnelIds->all())
                    ->get()
                : collect();

            if ($officeManualPersonnel->isNotEmpty()) {
                foreach ($officeManualPersonnel as $responder) {
                    $assignment = $this->createUniqueAssignment($actor, $incident, $office->id, $teamId, $responder->id, 'manual', $overrideReason);
                    if ($assignment) {
                        $createdAssignments->push($assignment);
                    }
                }

                continue;
            }

            if ($assignOfficeOnly || ! $ruleAutoDispatch) {
                $assignment = $this->createUniqueAssignment($actor, $incident, $office->id, $teamId, null, $assignmentType, $overrideReason);
                if ($assignment) {
                    $createdAssignments->push($assignment);
                }

                continue;
            }

            // Look for qualified AVAILABLE active personnel in this office
            $candidates = PersonnelProfile::with(['user', 'availability', 'teams'])
                ->where('office_id', $office->id)
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->whereHas('availability', fn ($q) => $q->where('operational_status', 'available'))
                ->when($teamId !== null, fn ($q) => $q->whereHas('teams', fn ($tq) => $tq->where('response_teams.id', $teamId)))
                ->get()
                ->filter(function (PersonnelProfile $p) use ($requiredQualifications): bool {
                    if ($requiredQualifications === []) {
                        return true;
                    }
                    $certs = array_map('strtolower', (array) ($p->qualifications ?? []));
                    foreach ($requiredQualifications as $req) {
                        if (! in_array(strtolower($req), $certs, true)) {
                            return false;
                        }
                    }

                    return true;
                })
                ->sortBy(function (PersonnelProfile $p): int {
                    return IncidentAssignment::where('personnel_id', $p->id)
                        ->where('status', 'active')
                        ->count();
                })
                ->take(max(1, $ruleRequiredCount));

            if ($candidates->isEmpty()) {
                if ($overrideReason !== null) {
                    $assignment = $this->createUniqueAssignment(
                        $actor,
                        $incident,
                        $office->id,
                        $teamId,
                        null,
                        'manual',
                        "Override reason: {$overrideReason}"
                    );
                    if ($assignment) {
                        $createdAssignments->push($assignment);
                    }
                } else {
                    $warnings[] = "{$office->name} has no available qualified personnel online.";
                    $this->notifyOfficeOfCoverageGap($incident, $office);
                }

                continue;
            }

            foreach ($candidates as $responder) {
                $assignment = $this->createUniqueAssignment($actor, $incident, $office->id, $teamId, $responder->id, $assignmentType, $overrideReason);
                if ($assignment) {
                    $createdAssignments->push($assignment);
                }
            }
        }

        // Notify assigned personnel & offices
        foreach ($createdAssignments as $assignment) {
            $assignment->loadMissing(['office', 'personnel.user']);

            if ($assignment->personnel?->user_id) {
                Notification::create([
                    'user_id' => $assignment->personnel->user_id,
                    'title' => 'EMERGENCY DISPATCH ORDER',
                    'body' => "Deployed to {$category->name} ({$incident->reference_number}) [".strtoupper($priority).'].',
                    'type' => 'incident_dispatch_order',
                    'action_url' => route('personnel.dashboard'),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            if ($assignment->office_id) {
                $officeUsers = Office::find($assignment->office_id)?->users()->get() ?? collect();
                foreach ($officeUsers as $officeUser) {
                    Notification::create([
                        'user_id' => $officeUser->id,
                        'title' => 'Incident Response Deployed',
                        'body' => "Incident {$incident->reference_number} ({$category->name}) assigned to {$assignment->office->name}.",
                        'type' => 'office_incident_dispatched',
                        'action_url' => route('office.dashboard'),
                        'is_read' => false,
                        'created_at' => now(),
                    ]);
                }
            }
        }

        $combinedWarning = $warnings !== [] ? implode(' ', $warnings) : null;
        if ($combinedWarning !== null) {
            $this->notifyAdminsOfDeploymentWarning($incident, $combinedWarning);
        }

        $dispatched = $createdAssignments->isNotEmpty();

        return [
            'assignments' => $createdAssignments,
            'warning' => $combinedWarning,
            'dispatched' => $dispatched,
        ];
    }

    protected function createUniqueAssignment(
        User $actor,
        Incident $incident,
        int $officeId,
        ?int $teamId,
        ?int $personnelId,
        string $assignmentType,
        ?string $notes
    ): ?IncidentAssignment {
        $exists = IncidentAssignment::where('incident_id', $incident->id)
            ->where('office_id', $officeId)
            ->where('status', 'active')
            ->when($personnelId !== null, fn ($q) => $q->where('personnel_id', $personnelId), fn ($q) => $q->whereNull('personnel_id'))
            ->exists();

        if ($exists) {
            return null;
        }

        return IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $officeId,
            'team_id' => $teamId,
            'personnel_id' => $personnelId,
            'assigned_by' => $actor->id,
            'assignment_type' => $assignmentType,
            'status' => 'active',
            'assigned_at' => now(),
            'notes' => $notes,
        ]);
    }

    protected function notifyAdminsOfDeploymentWarning(Incident $incident, string $warning): void
    {
        $admins = User::whereIn('account_type', ['admin', 'super_admin'])
            ->where('is_active', true)
            ->get(['id', 'account_type']);

        foreach ($admins as $admin) {
            $actionUrl = $admin->account_type === 'super_admin'
                ? route('super-admin.incidents.show', $incident->reference_number)
                : route('admin.incidents.show', $incident->reference_number);

            Notification::create([
                'user_id' => $admin->id,
                'title' => "Deployment Warning: {$incident->reference_number}",
                'body' => $warning,
                'type' => 'deployment_warning',
                'action_url' => $actionUrl,
                'is_read' => false,
                'created_at' => now(),
            ]);
        }
    }

    protected function notifyOfficeOfCoverageGap(Incident $incident, Office $office): void
    {
        foreach ($office->users()->wherePivot('is_office_admin', true)->get() as $officeAdmin) {
            Notification::create([
                'user_id' => $officeAdmin->id,
                'title' => "Responder Coverage Warning: {$incident->reference_number}",
                'body' => "A verified incident matched {$office->name}, but no qualified personnel are currently online/available.",
                'type' => 'coverage_warning',
                'action_url' => route('office.dashboard'),
                'is_read' => false,
                'created_at' => now(),
            ]);
        }
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
