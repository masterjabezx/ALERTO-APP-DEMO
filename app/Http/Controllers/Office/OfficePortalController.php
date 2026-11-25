<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficeUserRequest;
use App\Http\Requests\Admin\StorePersonnelRequest;
use App\Http\Requests\Office\OfficeReviewIncidentRequest;
use App\Models\Barangay;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Office;
use App\Models\User;
use App\Services\AdminIncidentService;
use App\Services\LiveOperationsMapService;
use App\Services\OfficeManagementService;
use App\Services\PersonnelManagementService;
use App\Support\CsvSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficePortalController extends Controller
{
    public function __construct(
        protected OfficeManagementService $officeService,
        protected AdminIncidentService $incidentService
    ) {}

    /**
     * Get authorized office for the authenticated user.
     */
    private function getAuthorizedOffice(): Office
    {
        $user = Auth::user();
        $office = $user->primaryOffice();

        if (! $office || ! $office->is_active) {
            abort(403, 'You are not assigned to an active office agency.');
        }

        return $office;
    }

    /**
     * 1. Office Dashboard: Scoped real-time KPIs, recent incidents, online personnel.
     */
    public function dashboard(): View
    {
        $office = $this->getAuthorizedOffice();
        $user = Auth::user();
        $officeUser = $user->officeUsers()->where('office_id', $office->id)->first();

        $stats = $this->officeService->getOfficeDashboardStats($office);
        $recentIncidents = $this->officeService->getOfficeIncidents($office, [], 5);
        $teams = $office->teams()->with(['members.user'])->get();
        $onlinePersonnel = $office->personnel()
            ->with(['user.userPresence', 'teams'])
            ->whereHas('user.userPresence', fn ($q) => $q->where('status', 'online'))
            ->get();

        return view('office.dashboard', [
            'user' => $user,
            'office' => $office,
            'officeUser' => $officeUser,
            'stats' => $stats,
            'recentIncidents' => $recentIncidents,
            'teams' => $teams,
            'onlinePersonnel' => $onlinePersonnel,
        ]);
    }

    /**
     * 2. Incident Monitoring: Search & filtered incident monitoring for authorized office scope.
     */
    public function incidents(Request $request): View
    {
        $office = $this->getAuthorizedOffice();
        $user = Auth::user();
        $officeUser = $user->officeUsers()->where('office_id', $office->id)->first();

        $filters = $request->only(['search', 'category_id', 'barangay_id', 'status', 'priority']);
        $incidents = $this->officeService->getOfficeIncidents($office, $filters, 12);

        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();

        return view('office.incidents.index', [
            'user' => $user,
            'office' => $office,
            'officeUser' => $officeUser,
            'incidents' => $incidents,
            'categories' => $categories,
            'barangays' => $barangays,
            'filters' => $filters,
        ]);
    }

    /**
     * Incident detail view scoped to authorized office.
     */
    public function showIncident(string $reference): View
    {
        $office = $this->getAuthorizedOffice();
        $user = Auth::user();
        $officeUser = $user->officeUsers()->where('office_id', $office->id)->first();

        $incident = Incident::with([
            'category',
            'barangay',
            'location',
            'media',
            'reviews.reviewer',
            'assignments.team',
            'assignments.personnel.user',
            'statusHistories.changedBy',
        ])
            ->where('reference_number', $reference)
            ->firstOrFail();

        // Security check: Must belong to office scope
        if (! $user->canReviewIncident($incident)) {
            $isAssigned = $incident->assignments()
                ->where(function ($q) use ($office) {
                    $q->where('office_id', $office->id)
                        ->orWhereIn('team_id', $office->teams->pluck('id'));
                })
                ->exists();

            if (! $isAssigned) {
                abort(403, 'Unauthorized access to restricted incident outside your agency jurisdiction.');
            }
        }

        return view('office.incidents.show', [
            'user' => $user,
            'office' => $office,
            'officeUser' => $officeUser,
            'incident' => $incident,
        ]);
    }

    /**
     * Review incident (Accept/Reject) by authorized Office Admin.
     */
    public function reviewIncident(OfficeReviewIncidentRequest $request, string $reference): RedirectResponse
    {
        $office = $this->getAuthorizedOffice();
        $user = Auth::user();

        $incident = Incident::where('reference_number', $reference)->firstOrFail();

        // Enforce review scope
        if (! $user->canReviewIncident($incident)) {
            abort(403, 'You do not have review authorization for this incident category or agency.');
        }

        try {
            $rawDecision = strtolower((string) ($request->input('decision') ?? $request->input('action')));
            $decision = in_array($rawDecision, ['accept', 'accepted'], true) ? 'accepted' : 'rejected';
            $reason = $request->input('rejection_reason');
            $notes = $request->input('review_remarks') ?? $request->input('review_notes') ?? $request->input('rejection_category');

            $this->incidentService->reviewIncident(
                $user,
                $incident,
                $decision,
                $reason,
                $notes
            );

            $msg = $decision === 'accepted'
                ? "Incident #{$incident->reference_number} accepted and queued for routing/dispatch."
                : "Incident #{$incident->reference_number} has been rejected.";

            return redirect()->route('office.incidents')->with('success', $msg);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 3. Office Incident Map: Dedicated spatial map showing office incidents, teams, and responders.
     */
    public function map(): View
    {
        $office = $this->getAuthorizedOffice();
        $mapData = $this->officeService->getOfficeMapData($office);

        return view('office.map', [
            'user' => Auth::user(),
            'office' => $office,
            'mapData' => $mapData,
        ]);
    }

    /**
     * JSON Endpoint for Office Map data.
     */
    public function mapData(Request $request): JsonResponse
    {
        $office = $this->getAuthorizedOffice();
        $mapService = app(LiveOperationsMapService::class);
        $data = $mapService->getOfficeMapData($office, $request->all());

        return response()->json($data);
    }

    /**
     * 4. Records & Reports: Historical archive and performance summaries.
     */
    public function records(Request $request): View
    {
        $office = $this->getAuthorizedOffice();
        $filters = $request->only(['timeframe', 'category_id', 'status']);
        $records = $this->officeService->getOfficeReportsData($office, $filters);

        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();

        return view('office.records', [
            'user' => Auth::user(),
            'office' => $office,
            'records' => $records,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Export reports to CSV.
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $office = $this->getAuthorizedOffice();
        $filters = $request->only(['timeframe', 'category_id', 'status']);
        $records = $this->officeService->getOfficeReportsData($office, $filters);

        $filename = "{$office->code}_Incident_Report_".now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Category', 'Barangay', 'Caller Name', 'Caller Phone', 'Status', 'Priority', 'Submitted At']);

            foreach ($records as $inc) {
                fputcsv($handle, CsvSanitizer::escapeRow([
                    $inc->reference_number,
                    $inc->category?->name ?? 'N/A',
                    $inc->barangay?->name ?? 'N/A',
                    $inc->caller_name ?? 'Confidential',
                    $inc->caller_phone ?? 'Protected',
                    strtoupper((string) $inc->status),
                    strtoupper((string) ($inc->priority ?? 'UNASSIGNED')),
                    $inc->submitted_at ? $inc->submitted_at->toDateTimeString() : '',
                ]));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * 5. Messages: Internal Messenger for Office communication with Admin.
     */
    public function messages(): View
    {
        $office = $this->getAuthorizedOffice();
        $user = Auth::user();

        // Get or create conversation with Command Center Admin or Super Admin
        $adminUser = User::whereIn('account_type', ['admin', 'super_admin'])
            ->orderByRaw("account_type = 'admin' DESC")
            ->first();
        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['messages.sender', 'participants.user'])
            ->latest('updated_at')
            ->get();

        return view('office.messages', [
            'user' => $user,
            'office' => $office,
            'adminUser' => $adminUser,
            'conversations' => $conversations,
        ]);
    }

    /**
     * 6. Office Personnel & Staff Roster (Strictly scoped to authenticated user's own Office).
     */
    public function personnel(): View
    {
        $office = $this->getAuthorizedOffice();
        $user = Auth::user();
        $officeUser = $user->officeUsers()->where('office_id', $office->id)->first();

        $personnel = $office->personnel()
            ->with(['user.userPresence', 'availability', 'teams'])
            ->latest('id')
            ->paginate(15);

        $officeStaff = $office->officeUsers()
            ->with('user')
            ->get();

        $teams = $office->teams()->where('is_active', true)->get();
        $barangays = Barangay::orderBy('name')->get();

        return view('office.personnel', [
            'user' => $user,
            'office' => $office,
            'officeUser' => $officeUser,
            'personnel' => $personnel,
            'officeStaff' => $officeStaff,
            'teams' => $teams,
            'barangays' => $barangays,
        ]);
    }

    /**
     * Office Admin (or authorized Office Staff) creates Personnel strictly under their own Office.
     */
    public function storePersonnel(StorePersonnelRequest $request, PersonnelManagementService $personnelService): RedirectResponse
    {
        $office = $this->getAuthorizedOffice();
        $actor = Auth::user();

        if (! $actor->canManageOfficeStaffAccounts()) {
            abort(403, 'You are not authorized to create personnel accounts for this office.');
        }

        $data = $request->validated();
        $data['office_id'] = $office->id; // Strictly override/enforce creator's own office

        $personnelService->createPersonnel($data, $actor);

        return back()->with('success', "Personnel responder created and assigned to {$office->name}.");
    }

    /**
     * Only authorized Office Admin (with can_manage_accounts granted by Admin/Super Admin) may create Office Staff under their own Office.
     */
    public function storeStaff(StoreOfficeUserRequest $request): RedirectResponse
    {
        $office = $this->getAuthorizedOffice();
        $actor = Auth::user();
        $officeUser = $actor->officeUsers()->where('office_id', $office->id)->first();

        if (! $officeUser || $officeUser->office_level !== 'office_admin' || ! $officeUser->can_manage_accounts) {
            abort(403, 'Creating Office Staff requires Office Admin authority with explicit account management permission from Command Center Admin or Super Admin.');
        }

        $data = $request->validated();
        $data['office_level'] = 'office_staff';
        $data['can_manage_accounts'] = false;

        $this->officeService->createOfficeUser($office, $data, $actor);

        return back()->with('success', "Office Staff account provisioned under {$office->name}.");
    }
}
