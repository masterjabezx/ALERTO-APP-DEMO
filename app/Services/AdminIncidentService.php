<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentContactVerification;
use App\Models\IncidentReview;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminIncidentService
{
    /**
     * Retrieve filtered, paginated incidents for Admin and Office management.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredIncidents(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Incident::with([
            'category',
            'barangay',
            'location',
            'activeAssignments.office',
            'activeAssignments.team',
            'activeAssignments.personnel.user',
            'reporter',
        ]);

        // Tab Filtering
        $tab = $filters['tab'] ?? 'overview';
        match ($tab) {
            'incoming' => $query->incomingReports(),
            'pending_review' => $query->whereIn('status', ['submitted', 'pending_review']),
            'accepted' => $query->where('status', 'accepted'),
            'dispatched' => $query->where('status', 'dispatched'),
            'active' => $query->whereIn('status', ['acknowledged', 'on_the_way', 'on_scene', 'in_progress']),
            'resolved' => $query->where('status', 'resolved'),
            'rejected' => $query->where('status', 'rejected'),
            'overview' => $query->whereIn('status', ['submitted', 'pending_review', 'accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress']),
            'all' => $query,
            default => $query,
        };

        // Specific Status Filter Override
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Search Term (reference, caller name, caller phone, description)
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('caller_name', 'like', "%{$search}%")
                    ->orWhere('caller_phone', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        // Barangay Filter
        if (! empty($filters['barangay_id'])) {
            $query->where('barangay_id', (int) $filters['barangay_id']);
        }

        // Priority Filter
        if (! empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        // Source Channel Filter
        if (! empty($filters['source_channel']) && $filters['source_channel'] !== 'all') {
            $query->where('source_channel', $filters['source_channel']);
        }

        // Contact Verification Status Filter
        if (! empty($filters['verification_status']) && $filters['verification_status'] !== 'all') {
            $query->where('contact_verification_status', $filters['verification_status']);
        }

        // Office Assignment Filter
        if (! empty($filters['office_id'])) {
            $query->whereHas('assignments', function ($q) use ($filters) {
                $q->where('office_id', (int) $filters['office_id'])
                    ->where('status', 'active');
            });
        }

        // Team Assignment Filter
        if (! empty($filters['team_id'])) {
            $query->whereHas('assignments', function ($q) use ($filters) {
                $q->where('team_id', (int) $filters['team_id'])
                    ->where('status', 'active');
            });
        }

        // Date Range
        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'submitted_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['submitted_at', 'reference_number', 'priority', 'status', 'accepted_at'], true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('submitted_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Compute comprehensive real-time statistics from live database records.
     *
     * @return array<string, mixed>
     */
    public function getIncidentStats(): array
    {
        $statusCounts = Incident::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($statusCounts);
        $submitted = $statusCounts['submitted'] ?? 0;
        $pending = ($statusCounts['pending_review'] ?? 0) + $submitted;
        $accepted = $statusCounts['accepted'] ?? 0;
        $dispatched = $statusCounts['dispatched'] ?? 0;
        $acknowledged = $statusCounts['acknowledged'] ?? 0;
        $onTheWay = $statusCounts['on_the_way'] ?? 0;
        $onScene = $statusCounts['on_scene'] ?? 0;
        $inProgress = $statusCounts['in_progress'] ?? 0;
        $resolved = $statusCounts['resolved'] ?? 0;
        $closed = $statusCounts['closed'] ?? 0;
        $rejected = $statusCounts['rejected'] ?? 0;

        $activeIncidents = $acknowledged + $onTheWay + $onScene + $inProgress;

        // Priority breakdown for active queue
        $criticalCount = Incident::where('priority', 'critical')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        $highCount = Incident::where('priority', 'high')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        // Category breakdown (supports unclassified reports)
        $categorySummary = DB::table('incidents')
            ->leftJoin('incident_categories', 'incidents.category_id', '=', 'incident_categories.id')
            ->select(DB::raw("COALESCE(incident_categories.name, 'Unclassified') as name"), DB::raw('count(*) as total'))
            ->groupBy('incident_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Barangay breakdown (supports unverified locations)
        $barangaySummary = DB::table('incidents')
            ->leftJoin('barangays', 'incidents.barangay_id', '=', 'barangays.id')
            ->select(DB::raw("COALESCE(barangays.name, 'Unverified Location') as name"), DB::raw('count(*) as total'))
            ->groupBy('barangays.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 7-Day Trend
        $dailyTrends = DB::table('incidents')
            ->select(DB::raw('DATE(submitted_at) as date'), DB::raw('count(*) as total'))
            ->where('submitted_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        return [
            'total' => $total,
            'pending_review' => $pending,
            'accepted' => $accepted,
            'dispatched' => $dispatched,
            'acknowledged' => $acknowledged,
            'active_incidents' => $activeIncidents,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'closed' => $closed,
            'rejected' => $rejected,
            'critical_count' => $criticalCount,
            'high_count' => $highCount,
            'category_summary' => $categorySummary,
            'barangay_summary' => $barangaySummary,
            'daily_trends' => $dailyTrends,
        ];
    }

    /**
     * Retrieve complete incident detail view with all relations.
     */
    public function getIncidentDetails(int|string $identifier): ?Incident
    {
        $query = Incident::with([
            'category',
            'barangay.municipality',
            'location',
            'statusHistories.changedBy',
            'evidenceRecords.media.geoMetadata',
            'reviews.reviewer',
            'assignments.office',
            'assignments.team',
            'assignments.personnel.user',
            'assignments.dispatcher',
            'contactVerifications.verifier',
            'reporter.citizenProfile',
            'reporter.citizenAddresses.barangay',
        ]);

        if (is_numeric($identifier)) {
            return $query->where('id', (int) $identifier)->first();
        }

        return $query->where('reference_number', $identifier)->first();
    }

    /**
     * Review incident: ACCEPT or REJECT with transaction and row lock.
     *
     * @throws InvalidArgumentException
     */
    public function reviewIncident(User $reviewer, Incident $incident, string $decision, ?string $reason = null, ?string $notes = null): IncidentReview
    {
        if (! in_array($decision, ['accepted', 'rejected'], true)) {
            throw new InvalidArgumentException("Invalid review decision: {$decision}");
        }

        if ($decision === 'rejected' && empty(trim((string) $reason))) {
            throw new InvalidArgumentException('A rejection reason is strictly required when rejecting an incident report.');
        }

        return DB::transaction(function () use ($reviewer, $incident, $decision, $reason, $notes) {
            // Lock incident row to prevent concurrent conflicting decisions
            $lockedIncident = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            if ($lockedIncident->status !== 'pending_review' && ! $reviewer->isAdmin()) {
                throw new InvalidArgumentException("Incident is not currently pending review (Current status: {$lockedIncident->status}).");
            }

            $previousStatus = $lockedIncident->status;
            $newStatus = $decision === 'accepted' ? 'accepted' : 'rejected';

            // Update Incident model
            $lockedIncident->status = $newStatus;
            if ($decision === 'accepted') {
                $lockedIncident->accepted_at = now();
            }
            $lockedIncident->version_lock++;
            $lockedIncident->save();

            // Lock original submitted evidence once accepted for legal chain-of-custody protection
            if ($decision === 'accepted') {
                app(MediaManagementService::class)->lockIncidentEvidence($lockedIncident);
            }

            // Record Incident Review
            $review = IncidentReview::create([
                'incident_id' => $lockedIncident->id,
                'reviewed_by' => $reviewer->id,
                'decision' => $decision,
                'rejection_reason' => $decision === 'rejected' ? $reason : null,
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            // Record Status History
            IncidentStatusHistory::create([
                'incident_id' => $lockedIncident->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => $reviewer->id,
                'change_reason' => $decision === 'accepted'
                    ? 'Report verified and accepted by triage. Initiating response routing.'
                    : "Report rejected: {$reason}",
                'created_at' => now(),
            ]);

            // Notify citizen reporter
            if ($lockedIncident->reporter_id) {
                $title = $decision === 'accepted'
                    ? 'Incident Report Accepted'
                    : 'Incident Report Rejected';

                $body = $decision === 'accepted'
                    ? "Your report {$lockedIncident->reference_number} has been verified and accepted by LDRRMO. Emergency response coordination is underway."
                    : "Your report {$lockedIncident->reference_number} was reviewed and rejected. Reason: {$reason}";

                Notification::create([
                    'user_id' => $lockedIncident->reporter_id,
                    'title' => $title,
                    'body' => $body,
                    'type' => $decision === 'accepted' ? 'incident_accepted' : 'incident_rejected',
                    'action_url' => route('citizen.records.show', $lockedIncident->reference_number),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            // Record Audit Log
            AuditLog::create([
                'user_id' => $reviewer->id,
                'event_type' => "incident:{$decision}",
                'auditable_type' => Incident::class,
                'auditable_id' => $lockedIncident->id,
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => $newStatus, 'decision' => $decision, 'notes' => $notes],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $review;
        });
    }

    /**
     * Admin override of previous review decision with mandatory reason and audit trail.
     */
    public function overrideReviewDecision(User $admin, Incident $incident, string $newDecision, string $overrideReason): IncidentReview
    {
        if (! $admin->isAdmin()) {
            throw new InvalidArgumentException('Only system administrators can execute emergency review overrides.');
        }

        if (empty(trim($overrideReason))) {
            throw new InvalidArgumentException('An override reason is mandatory.');
        }

        return DB::transaction(function () use ($admin, $incident, $newDecision, $overrideReason) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $locked->status;

            $locked->status = $newDecision;
            if ($newDecision === 'accepted' && ! $locked->accepted_at) {
                $locked->accepted_at = now();
            }
            $locked->version_lock++;
            $locked->save();

            $review = null;
            if (in_array($newDecision, ['accepted', 'rejected'], true)) {
                $review = IncidentReview::create([
                    'incident_id' => $locked->id,
                    'reviewed_by' => $admin->id,
                    'decision' => $newDecision,
                    'rejection_reason' => $newDecision === 'rejected' ? $overrideReason : null,
                    'review_notes' => "[ADMIN OVERRIDE]: {$overrideReason}",
                    'reviewed_at' => now(),
                ]);
            } else {
                $review = IncidentReview::where('incident_id', $locked->id)->latest('reviewed_at')->first()
                    ?? new IncidentReview([
                        'incident_id' => $locked->id,
                        'reviewed_by' => $admin->id,
                        'decision' => 'accepted',
                        'review_notes' => "[ADMIN OVERRIDE -> PENDING REVIEW]: {$overrideReason}",
                        'reviewed_at' => now(),
                    ]);
            }

            IncidentStatusHistory::create([
                'incident_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => $newDecision,
                'changed_by' => $admin->id,
                'change_reason' => "[ADMIN OVERRIDE]: {$overrideReason}",
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'event_type' => 'incident:review_overridden',
                'auditable_type' => Incident::class,
                'auditable_id' => $locked->id,
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => $newDecision, 'override_reason' => $overrideReason],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $review;
        });
    }

    /**
     * Update incident emergency priority level.
     */
    public function updateIncidentPriority(User $user, Incident $incident, string $newPriority, ?string $reason = null): Incident
    {
        if (! in_array($newPriority, ['critical', 'high', 'medium', 'low'], true)) {
            throw new InvalidArgumentException("Invalid priority level: {$newPriority}");
        }

        return DB::transaction(function () use ($user, $incident, $newPriority, $reason) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();
            $oldPriority = $locked->priority;

            $locked->priority = $newPriority;
            $locked->version_lock++;
            $locked->save();

            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'incident:priority_updated',
                'auditable_type' => Incident::class,
                'auditable_id' => $locked->id,
                'old_values' => ['priority' => $oldPriority],
                'new_values' => ['priority' => $newPriority, 'reason' => $reason],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $locked;
        });
    }

    /**
     * Record contact verification result (Callback or OTP verification).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordContactVerification(User $reviewer, Incident $incident, array $data): IncidentContactVerification
    {
        return DB::transaction(function () use ($reviewer, $incident, $data) {
            $verification = IncidentContactVerification::create([
                'incident_id' => $incident->id,
                'contact_number' => $data['contact_number'] ?? $incident->caller_phone,
                'verification_method' => $data['verification_method'] ?? 'callback',
                'status' => $data['status'],
                'verified_by' => $reviewer->id,
                'remarks' => $data['remarks'] ?? null,
                'verified_at' => now(),
            ]);

            $incident->contact_verification_status = $data['status'];
            $incident->save();

            AuditLog::create([
                'user_id' => $reviewer->id,
                'event_type' => 'incident:contact_verified',
                'auditable_type' => IncidentContactVerification::class,
                'auditable_id' => $verification->id,
                'new_values' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $verification;
        });
    }
}
