<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\EmergencyHotline;
use App\Models\FbConversation;
use App\Models\FbIntegrationSetting;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentIntakeRecord;
use App\Models\IncidentRoutingRule;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\ResponseLog;
use App\Models\SosAlert;
use App\Models\SystemBackupRecord;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserPresence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminCommandCenterService
{
    /**
     * Real-time badge counts and Notification Center records for Admin Layout.
     *
     * @return array<string, mixed>
     */
    public function getShellTelemetry(?User $admin): array
    {
        $pendingIncidents = Incident::whereIn('status', ['submitted', 'pending_review'])->count();
        $incomingReportsCount = Incident::incomingReports()->count();
        $awaitingCallCount = Incident::where('workflow_stage', 'awaiting_call')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();
        $activeSosCount = SosAlert::active()->count();
        $deploymentWarningsCount = Incident::where('has_assignment_warning', true)
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();
        $criticalActive = Incident::where('priority', 'critical')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        $fbUnreadCount = (int) FbConversation::sum('unread_count');
        $fbPendingIntakes = IncidentIntakeRecord::whereIn('status', ['new', 'incomplete', 'ready_for_review'])->count();

        $internalUnread = 0;
        if ($admin) {
            $internalUnread = (int) DB::table('messages')
                ->join('conversation_participants', 'messages.conversation_id', '=', 'conversation_participants.conversation_id')
                ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                ->where('conversations.is_archived', false)
                ->where('conversation_participants.user_id', $admin->id)
                ->where('messages.sender_id', '!=', $admin->id)
                ->whereRaw('messages.id > COALESCE(conversation_participants.last_read_message_id, 0)')
                ->whereNull('messages.deleted_at')
                ->count();
        }

        $notifications = collect();
        $unreadNotifications = 0;

        if ($admin) {
            $notifications = Notification::where('user_id', $admin->id)
                ->latest('created_at')
                ->limit(15)
                ->get();
            $unreadNotifications = Notification::where('user_id', $admin->id)
                ->where('is_read', false)
                ->count();
        }

        return [
            'pending_incidents' => $pendingIncidents,
            'incoming_reports_count' => $incomingReportsCount,
            'awaiting_call_count' => $awaitingCallCount,
            'active_sos_count' => $activeSosCount,
            'deployment_warnings_count' => $deploymentWarningsCount,
            'critical_active' => $criticalActive,
            'fb_unread_total' => $fbUnreadCount + $fbPendingIntakes,
            'internal_unread' => $internalUnread,
            'unread_notifications' => $unreadNotifications,
            'notifications' => $notifications,
        ];
    }

    /**
     * Complete real-database dashboard metrics, charts, presence counts, and activity feed.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getDashboardTelemetry(array $filters = []): array
    {
        $stats = $this->getExtendedStats();
        $presenceSummary = $this->getPresenceCounts();
        $charts = $this->getAnalyticsCharts($filters);
        $liveActivity = $this->getLiveActivityStream(12);

        $incomingReports = Incident::with(['barangay', 'location', 'processedByAdmin', 'callAttempts'])
            ->incomingReports()
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $activeSosAlerts = SosAlert::with(['barangay', 'office', 'user', 'personnel.user'])
            ->active()
            ->latest('triggered_at')
            ->limit(8)
            ->get();

        $highPriorityIncidents = Incident::with(['category', 'barangay', 'activeAssignments.office'])
            ->whereIn('priority', ['critical', 'high'])
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->latest('submitted_at')
            ->limit(6)
            ->get();

        $recentIncidents = Incident::with(['category', 'barangay', 'location', 'activeAssignments.office'])
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        return [
            'stats' => $stats,
            'presence' => $presenceSummary,
            'charts' => $charts,
            'live_activity' => $liveActivity,
            'incoming_reports' => $incomingReports,
            'active_sos_alerts' => $activeSosAlerts,
            'high_priority_incidents' => $highPriorityIncidents,
            'recent_incidents' => $recentIncidents,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Extended real database incident KPIs + historical comparison when actual data exists.
     *
     * @return array<string, mixed>
     */
    public function getExtendedStats(): array
    {
        $statusCounts = Incident::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = (int) array_sum($statusCounts);
        $submitted = (int) ($statusCounts['submitted'] ?? 0);
        $pending = (int) ($statusCounts['pending_review'] ?? 0) + $submitted;
        $accepted = (int) ($statusCounts['accepted'] ?? 0);
        $dispatched = (int) ($statusCounts['dispatched'] ?? 0);
        $active = (int) (
            ($statusCounts['acknowledged'] ?? 0)
            + ($statusCounts['on_the_way'] ?? 0)
            + ($statusCounts['on_scene'] ?? 0)
            + ($statusCounts['in_progress'] ?? 0)
        );
        $resolved = (int) (($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0));
        $rejected = (int) ($statusCounts['rejected'] ?? 0);
        $incomingReports = Incident::incomingReports()->count();
        $activeSos = SosAlert::active()->count();
        $deploymentWarnings = Incident::where('has_assignment_warning', true)
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();
        $criticalCount = Incident::where('priority', 'critical')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        // Real historical 7-day trend delta (only calculated when previous period baseline > 0)
        $current7d = Incident::where('submitted_at', '>=', now()->subDays(7))->count();
        $prior7d = Incident::whereBetween('submitted_at', [now()->subDays(14), now()->subDays(7)])->count();
        $trendPercent = $prior7d > 0 ? round((($current7d - $prior7d) / $prior7d) * 100, 1) : null;

        return [
            'total' => $total,
            'submitted' => $submitted,
            'pending_review' => $pending,
            'incoming_reports' => $incomingReports,
            'active_sos' => $activeSos,
            'deployment_warnings' => $deploymentWarnings,
            'accepted' => $accepted,
            'dispatched' => $dispatched,
            'active_incidents' => $active,
            'resolved' => $resolved,
            'rejected' => $rejected,
            'critical_count' => $criticalCount,
            'current_7d' => $current7d,
            'prior_7d' => $prior7d,
            'trend_percent' => $trendPercent,
        ];
    }

    /**
     * Real User Presence metrics strictly grouped by role & status (no Citizen GPS tracking).
     *
     * @return array<string, int>
     */
    public function getPresenceCounts(): array
    {
        $onlineByType = UserPresence::query()
            ->join('users', 'user_presences.user_id', '=', 'users.id')
            ->where('users.is_active', true)
            ->select('users.account_type', 'user_presences.status', DB::raw('count(*) as total'))
            ->groupBy('users.account_type', 'user_presences.status')
            ->get();

        $counts = [
            'personnel_online' => 0,
            'offices_online' => 0,
            'citizens_online' => 0,
            'admins_online' => 0,
            'super_admins_online' => 0,
            'idle_accounts' => 0,
            'offline_accounts' => 0,
            'connection_lost_accounts' => 0,
        ];

        foreach ($onlineByType as $row) {
            $status = (string) $row->status;
            $type = (string) $row->account_type;
            $qty = (int) $row->total;

            if ($status === 'online') {
                if ($type === 'personnel') {
                    $counts['personnel_online'] += $qty;
                } elseif ($type === 'office') {
                    $counts['offices_online'] += $qty;
                } elseif ($type === 'citizen') {
                    $counts['citizens_online'] += $qty;
                } elseif ($type === 'admin') {
                    $counts['admins_online'] += $qty;
                } elseif ($type === 'super_admin') {
                    $counts['super_admins_online'] += $qty;
                    $counts['admins_online'] += $qty;
                }
            } elseif ($status === 'idle') {
                $counts['idle_accounts'] += $qty;
            } elseif ($status === 'connection_lost') {
                $counts['connection_lost_accounts'] += $qty;
            } else {
                $counts['offline_accounts'] += $qty;
            }
        }

        return $counts;
    }

    /**
     * Live chronological operational activity stream built strictly from real database records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLiveActivityStream(int $limit = 12): array
    {
        $events = collect();

        $histories = IncidentStatusHistory::with(['incident.category', 'incident.barangay', 'changedBy'])
            ->latest('created_at')
            ->limit($limit)
            ->get();

        foreach ($histories as $h) {
            if (! $h->incident) {
                continue;
            }
            $events->push([
                'id' => 'hist-'.$h->id,
                'kind' => $h->new_status === 'dispatched' ? 'dispatch' : ($h->new_status === 'pending_review' ? 'submission' : 'status'),
                'title' => sprintf(
                    '%s → %s',
                    $h->incident->reference_number,
                    strtoupper(str_replace('_', ' ', $h->new_status))
                ),
                'subtitle' => ($h->incident->category?->name ?? 'Emergency').' · '.($h->incident->barangay?->name ?? 'Hinoba-an'),
                'detail' => $h->change_reason ?? ('Updated by '.($h->changedBy?->name ?? 'System')),
                'priority' => $h->incident->priority,
                'url' => route('admin.incidents.show', $h->incident->reference_number),
                'timestamp' => $h->created_at?->toIso8601String(),
                'time_ago' => $h->created_at?->diffForHumans() ?? 'Just now',
            ]);
        }

        $logs = ResponseLog::with(['incident', 'personnel.user'])
            ->latest('recorded_at')
            ->limit(6)
            ->get();

        foreach ($logs as $log) {
            if (! $log->incident) {
                continue;
            }
            $events->push([
                'id' => 'resp-'.$log->id,
                'kind' => 'personnel',
                'title' => sprintf(
                    'Responder %s · %s',
                    $log->personnel?->badge_number ?? 'UNIT',
                    strtoupper(str_replace('_', ' ', $log->milestone))
                ),
                'subtitle' => ($log->personnel?->user?->name ?? 'Field Operative').' · '.$log->incident->reference_number,
                'detail' => $log->notes ?? 'Field milestone telemetry confirmed.',
                'priority' => $log->incident->priority,
                'url' => route('admin.incidents.show', $log->incident->reference_number),
                'timestamp' => $log->recorded_at?->toIso8601String(),
                'time_ago' => $log->recorded_at?->diffForHumans() ?? 'Just now',
            ]);
        }

        return $events->sortByDesc('timestamp')->take($limit)->values()->toArray();
    }

    /**
     * Real database analytical datasets for Chart.js visualizations.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getAnalyticsCharts(array $filters = []): array
    {
        $baseQuery = function () use ($filters): Builder {
            $q = Incident::query();
            if (! empty($filters['date_from'])) {
                $q->whereDate('submitted_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $q->whereDate('submitted_at', '<=', $filters['date_to']);
            }
            if (! empty($filters['category_id'])) {
                $q->where('category_id', (int) $filters['category_id']);
            }
            if (! empty($filters['barangay_id'])) {
                $q->where('barangay_id', (int) $filters['barangay_id']);
            }

            return $q;
        };

        // 1. Incidents by Category (supports unclassified reports)
        $catRows = $baseQuery()
            ->leftJoin('incident_categories', 'incidents.category_id', '=', 'incident_categories.id')
            ->select(
                DB::raw("COALESCE(incident_categories.name, 'Unclassified') as name"),
                DB::raw("COALESCE(incident_categories.color_code, '#F59E0B') as color_code"),
                DB::raw('count(*) as total')
            )
            ->groupBy('incident_categories.id', 'incident_categories.name', 'incident_categories.color_code')
            ->orderByDesc('total')
            ->get();

        // 2. Incidents by Barangay (Top 10, supports unverified locations)
        $brgyRows = $baseQuery()
            ->leftJoin('barangays', 'incidents.barangay_id', '=', 'barangays.id')
            ->select(DB::raw("COALESCE(barangays.name, 'Unverified Location') as name"), DB::raw('count(*) as total'))
            ->groupBy('barangays.id', 'barangays.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 3. Incidents by Status
        $statusRows = $baseQuery()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        // 4. Monthly Incident Trends (last 6 months)
        $incidentsForTrend = $baseQuery()
            ->select(['submitted_at', 'accepted_at', 'resolved_at', 'status'])
            ->where('submitted_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get();

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $key = $m->format('Y-m');
            $months[$key] = ['label' => $m->format('M Y'), 'submitted' => 0, 'resolved' => 0];
        }

        foreach ($incidentsForTrend as $row) {
            if ($row->submitted_at) {
                $k = Carbon::parse($row->submitted_at)->format('Y-m');
                if (isset($months[$k])) {
                    $months[$k]['submitted']++;
                    if (in_array($row->status, ['resolved', 'closed'], true)) {
                        $months[$k]['resolved']++;
                    }
                }
            }
        }

        // 5. Response Performance (real average duration in minutes)
        $allTimingIncidents = $baseQuery()
            ->select(['submitted_at', 'accepted_at', 'resolved_at'])
            ->whereNotNull('submitted_at')
            ->get();

        $acceptMinutes = [];
        $resolveMinutes = [];
        foreach ($allTimingIncidents as $inc) {
            if ($inc->accepted_at && $inc->submitted_at) {
                $diff = Carbon::parse($inc->submitted_at)->diffInMinutes(Carbon::parse($inc->accepted_at));
                if ($diff >= 0) {
                    $acceptMinutes[] = $diff;
                }
            }
            if ($inc->resolved_at && $inc->submitted_at) {
                $diff = Carbon::parse($inc->submitted_at)->diffInMinutes(Carbon::parse($inc->resolved_at));
                if ($diff >= 0) {
                    $resolveMinutes[] = $diff;
                }
            }
        }

        $avgAcceptMin = count($acceptMinutes) > 0 ? round(array_sum($acceptMinutes) / count($acceptMinutes), 1) : 0;
        $avgResolveMin = count($resolveMinutes) > 0 ? round(array_sum($resolveMinutes) / count($resolveMinutes), 1) : 0;

        // 6. Office Activity (assignments per active office)
        $officeRows = Office::query()
            ->leftJoin('incident_assignments', 'offices.id', '=', 'incident_assignments.office_id')
            ->select('offices.code', 'offices.name', DB::raw('count(incident_assignments.id) as assignments_count'))
            ->where('offices.is_active', true)
            ->groupBy('offices.id', 'offices.code', 'offices.name')
            ->orderByDesc('assignments_count')
            ->limit(8)
            ->get();

        // 7. Personnel Activity (assignments & response logs)
        $personnelRows = PersonnelProfile::query()
            ->join('users', 'personnel_profiles.user_id', '=', 'users.id')
            ->leftJoin('incident_assignments', 'personnel_profiles.id', '=', 'incident_assignments.personnel_id')
            ->select('users.name', 'personnel_profiles.badge_number', DB::raw('count(incident_assignments.id) as tasks_count'))
            ->groupBy('personnel_profiles.id', 'users.name', 'personnel_profiles.badge_number')
            ->orderByDesc('tasks_count')
            ->limit(8)
            ->get();

        return [
            'by_category' => [
                'labels' => $catRows->pluck('name')->values()->toArray(),
                'data' => $catRows->pluck('total')->map(fn ($v) => (int) $v)->values()->toArray(),
                'colors' => $catRows->map(fn ($r) => $r->color_code ?: '#3187D8')->values()->toArray(),
            ],
            'by_barangay' => [
                'labels' => $brgyRows->pluck('name')->values()->toArray(),
                'data' => $brgyRows->pluck('total')->map(fn ($v) => (int) $v)->values()->toArray(),
            ],
            'by_status' => [
                'labels' => $statusRows->pluck('status')->map(fn ($s) => strtoupper(str_replace('_', ' ', (string) $s)))->values()->toArray(),
                'data' => $statusRows->pluck('total')->map(fn ($v) => (int) $v)->values()->toArray(),
            ],
            'monthly_trends' => [
                'labels' => array_column($months, 'label'),
                'submitted' => array_column($months, 'submitted'),
                'resolved' => array_column($months, 'resolved'),
            ],
            'response_performance' => [
                'avg_accept_minutes' => $avgAcceptMin,
                'avg_resolve_minutes' => $avgResolveMin,
                'sampled_accepts' => count($acceptMinutes),
                'sampled_resolutions' => count($resolveMinutes),
            ],
            'office_activity' => [
                'labels' => $officeRows->pluck('code')->values()->toArray(),
                'names' => $officeRows->pluck('name')->values()->toArray(),
                'data' => $officeRows->pluck('assignments_count')->map(fn ($v) => (int) $v)->values()->toArray(),
            ],
            'personnel_activity' => [
                'labels' => $personnelRows->map(fn ($p) => $p->badge_number ?: $p->name)->values()->toArray(),
                'data' => $personnelRows->pluck('tasks_count')->map(fn ($v) => (int) $v)->values()->toArray(),
            ],
        ];
    }

    /**
     * Live User Monitor dataset grouped strictly by account role and presence status.
     * Note: Never tracks Citizen physical GPS coordinates.
     *
     * @return array<string, mixed>
     */
    public function getLiveUserMonitorData(?string $accountType = null, ?string $statusFilter = null): array
    {
        $summary = $this->getPresenceCounts();

        $query = User::query()
            ->with(['userPresence', 'personnelProfile.office', 'personnelProfile.availability', 'offices'])
            ->where('is_active', true);

        if ($accountType && $accountType !== 'all') {
            $query->where('account_type', $accountType);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->whereHas('userPresence', fn (Builder $q) => $q->where('status', $statusFilter));
        }

        $users = $query->orderByRaw("CASE WHEN account_type = 'super_admin' THEN 0 WHEN account_type = 'admin' THEN 1 WHEN account_type = 'office' THEN 2 WHEN account_type = 'personnel' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get()
            ->map(function (User $u): array {
                $presence = $u->userPresence;
                $status = $presence?->status ?? 'offline';
                $affiliation = match ($u->account_type) {
                    'super_admin' => 'LDRRMO Super Admin Authority',
                    'personnel' => ($u->personnelProfile?->badge_number ? '#'.$u->personnelProfile->badge_number.' · ' : '').($u->personnelProfile?->office?->code ?? 'Responder'),
                    'office' => $u->offices->pluck('code')->implode(', ') ?: 'Agency Desk',
                    'admin' => 'DIAL DAPH Command Center',
                    default => 'Registered Citizen Portal User',
                };

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone_number' => $u->phone_number,
                    'account_type' => $u->account_type,
                    'affiliation' => $affiliation,
                    'operational_status' => $u->account_type === 'personnel' ? ($u->personnelProfile?->availability?->operational_status ?? 'offline') : null,
                    'presence_status' => $status,
                    'last_heartbeat_at' => $presence?->last_heartbeat_at?->toIso8601String(),
                    'last_heartbeat_human' => $presence?->last_heartbeat_at?->diffForHumans() ?? 'No recorded heartbeat',
                    'current_device' => $presence?->current_device ?? 'Web Terminal',
                ];
            });

        return [
            'summary' => $summary,
            'users' => $users,
            'super_admin_users' => $users->where('account_type', 'super_admin')->values(),
            'personnel_users' => $users->where('account_type', 'personnel')->values(),
            'office_users' => $users->where('account_type', 'office')->values(),
            'citizen_users' => $users->where('account_type', 'citizen')->values(),
            'admin_users' => $users->where('account_type', 'admin')->values(),
        ];
    }

    /**
     * Filterable Reports & Analytics dataset.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportsDataset(array $filters = []): array
    {
        $query = Incident::with(['category', 'barangay', 'activeAssignments.office', 'activeAssignments.team'])
            ->latest('submitted_at');

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['barangay_id'])) {
            $query->where('barangay_id', (int) $filters['barangay_id']);
        }
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $incidents = $query->paginate(20)->withQueryString();
        $charts = $this->getAnalyticsCharts($filters);
        $categories = IncidentCategory::orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();

        return [
            'incidents' => $incidents,
            'charts' => $charts,
            'categories' => $categories,
            'barangays' => $barangays,
        ];
    }

    /**
     * Filtered Audit & Activity logs explorer.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getLogsDataset(array $filters = []): array
    {
        $auditQuery = AuditLog::with('user')->latest('created_at');
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $auditQuery->where(function (Builder $q) use ($term): void {
                $q->where('event_type', 'like', "%{$term}%")
                    ->orWhere('auditable_type', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$term}%"));
            });
        }
        if (! empty($filters['event_type'])) {
            $auditQuery->where('event_type', 'like', "%{$filters['event_type']}%");
        }
        if (! empty($filters['date_from'])) {
            $auditQuery->whereDate('created_at', '>=', $filters['date_from']);
        }

        $auditLogs = $auditQuery->paginate(25)->withQueryString();
        $activityLogs = ActivityLog::with('user')->latest('created_at')->limit(20)->get();

        return [
            'auditLogs' => $auditLogs,
            'activityLogs' => $activityLogs,
        ];
    }

    /**
     * Command Center & System Settings summary.
     *
     * @return array<string, mixed>
     */
    public function getSettingsWorkspaceData(): array
    {
        return [
            'hotline' => SystemSetting::get('dial_daph_command_center_hotline', '0917-123-4567'),
            'hotline_title' => SystemSetting::get('dial_daph_command_center_title', 'DIAL DAPH COMMAND CENTER'),
            'facebook_url' => SystemSetting::get('official_facebook_page_url', 'https://facebook.com/hinobaan.ldrmo'),
            'lat_min' => SystemSetting::get('municipal_boundary_lat_min', '9.4000'),
            'lat_max' => SystemSetting::get('municipal_boundary_lat_max', '9.7500'),
            'lng_min' => SystemSetting::get('municipal_boundary_lng_min', '122.3500'),
            'lng_max' => SystemSetting::get('municipal_boundary_lng_max', '122.6500'),
            'hotlines' => EmergencyHotline::with('office')->orderBy('sort_order')->get(),
            'routing_rules' => IncidentRoutingRule::with(['category', 'barangay', 'targetOffice'])->where('is_active', true)->get(),
            'backups' => SystemBackupRecord::latest()->limit(10)->get(),
            'fb_setting' => FbIntegrationSetting::first(),
            'all_settings' => SystemSetting::orderBy('key')->get(),
        ];
    }
}
