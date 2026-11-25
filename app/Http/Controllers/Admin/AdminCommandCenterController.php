<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEocSettingsRequest;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\Notification;
use App\Models\Office;
use App\Models\ResponseTeam;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\AdminCommandCenterService;
use App\Services\HotlineService;
use App\Support\CsvSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCommandCenterController extends Controller
{
    public function __construct(
        protected AdminCommandCenterService $commandService
    ) {}

    /**
     * Real-time Dashboard Telemetry & Charts JSON API.
     */
    public function telemetry(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'category_id', 'barangay_id']);
        $data = $this->commandService->getDashboardTelemetry($filters);
        $shell = $this->commandService->getShellTelemetry(Auth::user());

        return response()->json([
            'telemetry' => $data,
            'shell' => [
                'pending_incidents' => $shell['pending_incidents'],
                'incoming_reports_count' => $shell['incoming_reports_count'],
                'awaiting_call_count' => $shell['awaiting_call_count'],
                'active_sos_count' => $shell['active_sos_count'],
                'deployment_warnings_count' => $shell['deployment_warnings_count'],
                'critical_active' => $shell['critical_active'],
                'fb_unread_total' => $shell['fb_unread_total'],
                'internal_unread' => $shell['internal_unread'],
                'unread_notifications' => $shell['unread_notifications'],
                'notifications' => $shell['notifications'],
            ],
        ]);
    }

    /**
     * Live User Presence Monitor (Admin, Office, Personnel, Citizen account presence).
     */
    public function usersMonitor(Request $request): View
    {
        $accountType = $request->query('account_type', 'all');
        $statusFilter = $request->query('status', 'all');

        $monitor = $this->commandService->getLiveUserMonitorData(
            is_string($accountType) ? $accountType : null,
            is_string($statusFilter) ? $statusFilter : null
        );

        return view('admin.monitor.users', [
            'monitor' => $monitor,
            'accountType' => $accountType,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Live User Monitor polling JSON endpoint.
     */
    public function usersMonitorData(Request $request): JsonResponse
    {
        $accountType = $request->query('account_type', 'all');
        $statusFilter = $request->query('status', 'all');

        $monitor = $this->commandService->getLiveUserMonitorData(
            is_string($accountType) ? $accountType : null,
            is_string($statusFilter) ? $statusFilter : null
        );

        return response()->json($monitor);
    }

    /**
     * Admin Notification Center full page history.
     */
    public function notifications(Request $request): View
    {
        /** @var User $admin */
        $admin = Auth::user();

        $notifications = Notification::where('user_id', $admin->id)
            ->latest('created_at')
            ->paginate(20);

        $unreadCount = Notification::where('user_id', $admin->id)
            ->where('is_read', false)
            ->count();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark single Admin notification as read.
     */
    public function markNotificationRead(int $id): JsonResponse|RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();

        $notification = Notification::where('user_id', $admin->id)->findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['status' => 'ok', 'id' => $id]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all Admin notifications as read.
     */
    public function markAllNotificationsRead(): JsonResponse|RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();

        Notification::where('user_id', $admin->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if (request()->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('success', 'All command notifications marked as read.');
    }

    /**
     * Admin Reports & Analytics Workspace.
     */
    public function reports(Request $request): View
    {
        $filters = $request->only(['date_from', 'date_to', 'category_id', 'barangay_id', 'status']);
        $dataset = $this->commandService->getReportsDataset($filters);
        $stats = $this->commandService->getExtendedStats();

        return view('admin.reports.index', [
            'incidents' => $dataset['incidents'],
            'charts' => $dataset['charts'],
            'categories' => $dataset['categories'],
            'barangays' => $dataset['barangays'],
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Export filtered incident analytics as CSV/Excel compatible stream.
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'category_id', 'barangay_id', 'status']);
        $query = Incident::with(['category', 'barangay', 'activeAssignments.office'])
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

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Reference Number',
                'Category',
                'Barangay',
                'Priority',
                'Status',
                'Source Channel',
                'Assigned Office',
                'Submitted At',
                'Accepted At',
                'Resolved At',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, CsvSanitizer::escapeRow([
                    $row->reference_number,
                    $row->category?->name ?? 'Unclassified Emergency',
                    $row->barangay?->name ?? 'Unverified Location',
                    strtoupper((string) ($row->priority ?? 'UNASSIGNED')),
                    strtoupper((string) $row->status),
                    $row->source_channel,
                    $row->activeAssignments->first()?->office?->name ?? 'Unassigned',
                    $row->submitted_at?->toDateTimeString() ?? '',
                    $row->accepted_at?->toDateTimeString() ?? '',
                    $row->resolved_at?->toDateTimeString() ?? '',
                ]));
            }

            fclose($handle);
        }, 'alerto-eoc-incidents-report-'.date('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Activity & Audit Logs Explorer.
     */
    public function auditLogs(Request $request): View
    {
        $filters = $request->only(['search', 'event_type', 'date_from']);
        $dataset = $this->commandService->getLogsDataset($filters);

        return view('admin.audit.index', [
            'auditLogs' => $dataset['auditLogs'],
            'activityLogs' => $dataset['activityLogs'],
            'filters' => $filters,
        ]);
    }

    /**
     * Command Center Settings page (includes dual configurable emergency hotlines).
     */
    public function eocSettings(HotlineService $hotlineService): View
    {
        $data = $this->commandService->getSettingsWorkspaceData();
        $data['hotlinesConfig'] = $hotlineService->getDualHotlines();

        return view('admin.eoc-settings.index', $data);
    }

    /**
     * Save Command Center Settings and dual emergency hotlines.
     */
    public function updateEocSettings(UpdateEocSettingsRequest $request, HotlineService $hotlineService): RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();
        $data = $request->validated();

        $hotlineService->updateDualHotlines($admin, [
            'dial_daph_command_center_title' => $data['dial_daph_command_center_title'] ?? $data['hotline_title'] ?? 'DIAL DAPH COMMAND CENTER',
            'dial_daph_command_center_hotline' => $data['dial_daph_command_center_hotline'] ?? $data['hotline'] ?? '',
            'dial_daph_command_center_description' => $data['dial_daph_command_center_description'] ?? SystemSetting::get('dial_daph_command_center_description', ''),
            'dial_daph_command_center_active' => $request->has('dial_daph_command_center_active')
                ? $request->boolean('dial_daph_command_center_active')
                : true,
            'ldrrmo_hotline_title' => $data['ldrrmo_hotline_title'] ?? SystemSetting::get('ldrrmo_hotline_title', 'LDRRMO'),
            'ldrrmo_hotline_number' => $data['ldrrmo_hotline_number'] ?? SystemSetting::get('ldrrmo_hotline_number', ''),
            'ldrrmo_hotline_description' => $data['ldrrmo_hotline_description'] ?? SystemSetting::get('ldrrmo_hotline_description', ''),
            'ldrrmo_hotline_active' => $request->has('ldrrmo_hotline_active')
                ? $request->boolean('ldrrmo_hotline_active')
                : (bool) SystemSetting::get('ldrrmo_hotline_active', true),
        ]);

        SystemSetting::set('official_facebook_page_url', $data['facebook_url'], 'string');
        SystemSetting::set('municipal_boundary_lat_min', (string) $data['lat_min'], 'string');
        SystemSetting::set('municipal_boundary_lat_max', (string) $data['lat_max'], 'string');
        SystemSetting::set('municipal_boundary_lng_min', (string) $data['lng_min'], 'string');
        SystemSetting::set('municipal_boundary_lng_max', (string) $data['lng_max'], 'string');

        AuditLog::create([
            'user_id' => $admin->id,
            'event_type' => 'eoc:settings_updated',
            'auditable_type' => SystemSetting::class,
            'auditable_id' => 1,
            'new_values' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Command Center dual emergency hotlines and geographic bounds updated.');
    }

    /**
     * Dispatch & Unit Coordination Console for Command Center Admin & Super Admin.
     */
    public function dispatchConsole(): View
    {
        $awaitingDispatch = Incident::with(['category', 'barangay', 'location', 'assessment', 'activeAssignments.office', 'activeAssignments.personnel.user'])
            ->where(function ($q): void {
                $q->where('status', 'accepted')
                    ->orWhere('has_assignment_warning', true);
            })
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->latest('accepted_at')
            ->get();

        $activeAssignments = IncidentAssignment::with(['incident.category', 'incident.barangay', 'office', 'team', 'personnel.user'])
            ->where('status', 'active')
            ->latest('assigned_at')
            ->paginate(20);

        $offices = Office::where('is_active', true)->with(['teams', 'personnel.availability'])->orderBy('name')->get();
        $teams = ResponseTeam::where('is_active', true)->with('office')->orderBy('team_name')->get();

        return view('admin.dispatch.index', compact('awaitingDispatch', 'activeAssignments', 'offices', 'teams'));
    }

    /**
     * Citizen Account Directory & Management for Command Center Admin & Super Admin.
     */
    public function citizens(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $citizens = User::with(['citizenProfile', 'citizenAddresses.barangay', 'userPresence'])
            ->withCount('reportedIncidents')
            ->where('account_type', 'citizen')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.citizens.index', compact('citizens', 'search'));
    }

    /**
     * Activate or deactivate a Citizen account cleanly with audit logging.
     */
    public function toggleCitizenStatus(int $id): RedirectResponse
    {
        $actor = Auth::user();
        $citizen = User::where('account_type', 'citizen')->findOrFail($id);

        $citizen->is_active = ! $citizen->is_active;
        $citizen->save();

        if (! $citizen->is_active) {
            UserPresence::where('user_id', $citizen->id)->update(['status' => 'offline']);
        }

        AuditLog::create([
            'user_id' => $actor->id,
            'event_type' => $citizen->is_active ? 'citizen:activated' : 'citizen:deactivated',
            'auditable_type' => User::class,
            'auditable_id' => $citizen->id,
            'new_values' => ['is_active' => $citizen->is_active],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', "Citizen account {$citizen->name} ".($citizen->is_active ? 'activated.' : 'deactivated.'));
    }

    /**
     * System Settings & Backups Registry.
     */
    public function systemSettings(): View
    {
        $data = $this->commandService->getSettingsWorkspaceData();

        return view('admin.settings.index', $data);
    }

    /**
     * Save System Settings.
     */
    public function updateSystemSettings(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();
        $data = $request->validated();

        SystemSetting::set('max_upload_size_mb', (int) $data['max_upload_size_mb'], 'integer');
        SystemSetting::set('max_upload_count', (int) $data['max_upload_count'], 'integer');

        AuditLog::create([
            'user_id' => $admin->id,
            'event_type' => 'system:settings_updated',
            'auditable_type' => SystemSetting::class,
            'auditable_id' => 1,
            'new_values' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'System operational settings updated cleanly.');
    }
}
