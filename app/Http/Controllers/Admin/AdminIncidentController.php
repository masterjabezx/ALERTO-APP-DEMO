<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssessAndDeployRequest;
use App\Http\Requests\Admin\AssignIncidentRequest;
use App\Http\Requests\Admin\CallOutcomeRequest;
use App\Http\Requests\Admin\DispatchIncidentRequest;
use App\Http\Requests\Admin\ReviewIncidentRequest;
use App\Http\Requests\Admin\UpdatePriorityRequest;
use App\Http\Requests\Admin\VerifyContactRequest;
use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\ResponseTeam;
use App\Services\AdminIncidentService;
use App\Services\EmergencyAssessmentService;
use App\Services\HotlineService;
use App\Services\IncidentRoutingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminIncidentController extends Controller
{
    public function __construct(
        protected AdminIncidentService $incidentService,
        protected IncidentRoutingService $routingService
    ) {}

    /**
     * Searchable, filterable incident management list with 8 tabs and real database metrics.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Incident::class);

        $filters = $request->only([
            'tab',
            'search',
            'category_id',
            'barangay_id',
            'status',
            'priority',
            'source_channel',
            'verification_status',
            'office_id',
            'team_id',
            'date_from',
            'date_to',
            'sort_by',
            'sort_order',
        ]);

        $incidents = $this->incidentService->getFilteredIncidents($filters, 15);
        $stats = $this->incidentService->getIncidentStats();

        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();
        $offices = Office::where('is_active', true)->with('teams')->orderBy('name')->get();
        $teams = ResponseTeam::orderBy('team_name')->get();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'stats' => $stats,
            'categories' => $categories,
            'barangays' => $barangays,
            'offices' => $offices,
            'teams' => $teams,
            'filters' => $filters,
            'currentTab' => $filters['tab'] ?? 'overview',
        ]);
    }

    /**
     * Incoming Unclassified Citizen & Guest Reports Queue (awaiting call verification).
     */
    public function incoming(Request $request): View
    {
        Gate::authorize('viewAny', Incident::class);

        $stage = $request->query('stage', 'all');
        $query = Incident::with(['barangay', 'location', 'processedByAdmin', 'callAttempts'])
            ->incomingReports()
            ->latest('submitted_at');

        if ($stage !== 'all' && in_array($stage, ['new_report', 'awaiting_call', 'assessment_in_progress'], true)) {
            $query->where('workflow_stage', $stage);
        }

        $reports = $query->paginate(20)->withQueryString();

        return view('admin.incoming.index', compact('reports', 'stage'));
    }

    /**
     * Comprehensive incident detail, call verification, emergency assessment, and dispatch controls.
     */
    public function show(string $reference, EmergencyAssessmentService $assessmentService, HotlineService $hotlineService): View
    {
        $incident = $this->incidentService->getIncidentDetails($reference);

        if (! $incident) {
            abort(404, 'Incident not found.');
        }

        Gate::authorize('view', $incident);

        if ($incident->isUnclassifiedReport() || $incident->processed_by_admin_id === null) {
            $assessmentService->markReportViewed(Auth::user(), $incident);
            $incident = $this->incidentService->getIncidentDetails($reference) ?? $incident;
        }

        $incident->loadMissing([
            'callAttempts.initiator',
            'assessment.category',
            'assessment.verifiedBarangay',
            'assessment.assessor',
            'processedByAdmin',
            'overriddenBySuperAdmin',
        ]);

        $routingRecommendation = $this->routingService->recommendRouting($incident);
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();
        $offices = Office::where('is_active', true)->with(['teams', 'personnel.user', 'personnel.availability'])->get();
        $allPersonnel = PersonnelProfile::with(['user', 'office', 'availability'])->get();
        $callerTelHref = $hotlineService->sanitizeTelUri($incident->caller_phone);

        return view('admin.incidents.show', [
            'incident' => $incident,
            'routing' => $routingRecommendation,
            'categories' => $categories,
            'barangays' => $barangays,
            'offices' => $offices,
            'allPersonnel' => $allPersonnel,
            'callerTelHref' => $callerTelHref,
        ]);
    }

    /**
     * Log CALL CITIZEN initiation from DIAL DAPH Command Center / Super Admin.
     */
    public function initiateCall(Request $request, string $reference, EmergencyAssessmentService $assessmentService, HotlineService $hotlineService): JsonResponse|RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('callCitizen', $incident);

        $attempt = $assessmentService->initiateCallAttempt(Auth::user(), $incident, $request->input('phone_number'));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'attempt_id' => $attempt->id,
                'phone_number_dialed' => $attempt->phone_number_dialed,
                'tel_href' => $hotlineService->sanitizeTelUri($attempt->phone_number_dialed),
                'initiated_at' => $attempt->initiated_at?->toIso8601String(),
                'workflow_stage' => $incident->fresh()->workflow_stage,
            ], 201);
        }

        return back()->with('success', "Call verification logged toward {$attempt->phone_number_dialed}. Complete call outcome and Emergency Assessment below.");
    }

    /**
     * Record call outcome & verification notes.
     */
    public function recordCallOutcome(CallOutcomeRequest $request, string $reference, EmergencyAssessmentService $assessmentService): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('callCitizen', $incident);

        $attempt = $assessmentService->recordCallOutcome(Auth::user(), $incident, $request->validated());

        return back()->with('success', 'Call attempt outcome recorded: '.strtoupper(str_replace('_', ' ', $attempt->call_outcome)).'.');
    }

    /**
     * Save emergency assessment, classify incident, evaluate routing rules & deploy response.
     */
    public function assessAndDeploy(AssessAndDeployRequest $request, string $reference, EmergencyAssessmentService $assessmentService): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('assess', $incident);

        $result = $assessmentService->confirmAssessmentAndDeploy(Auth::user(), $incident, $request->validated());

        if ($result['warning'] !== null && $result['assignments']->isEmpty()) {
            return back()->with('warning', 'Assessment saved and incident accepted, but dispatch paused: '.$result['warning']);
        }

        if ($result['warning'] !== null) {
            return back()->with('success', "Emergency classified & {$result['assignments']->count()} response unit(s) deployed. Notice: {$result['warning']}");
        }

        return back()->with('success', "Emergency classified as {$result['assessment']->category?->name} and {$result['assignments']->count()} response unit(s) automatically deployed!");
    }

    /**
     * Review incident: ACCEPT or REJECT report with concurrency locking.
     */
    public function review(ReviewIncidentRequest $request, string $reference): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('review', $incident);

        $user = Auth::user();
        $validated = $request->validated();

        try {
            $this->incidentService->reviewIncident(
                $user,
                $incident,
                $validated['decision'],
                $validated['rejection_reason'] ?? null,
                $validated['review_notes'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.incidents.show', $reference)->with('error', $e->getMessage());
        }

        $msg = $validated['decision'] === 'accepted'
            ? "Incident {$reference} has been accepted. Routing and assignment recommendations generated."
            : "Incident {$reference} has been rejected. Reporting citizen notified.";

        return redirect()->route('admin.incidents.show', $reference)->with('success', $msg);
    }

    /**
     * Admin emergency override of previous review decision.
     */
    public function override(Request $request, string $reference): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('override', $incident);

        $request->validate([
            'new_decision' => ['required', 'in:accepted,rejected,pending_review'],
            'override_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->incidentService->overrideReviewDecision(
                Auth::user(),
                $incident,
                $request->input('new_decision'),
                $request->input('override_reason')
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.incidents.show', $reference)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.incidents.show', $reference)
            ->with('success', "Administrative override executed for incident {$reference}.");
    }

    /**
     * Update incident emergency priority level.
     */
    public function updatePriority(UpdatePriorityRequest $request, string $reference): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('updatePriority', $incident);

        $validated = $request->validated();

        try {
            $this->incidentService->updateIncidentPriority(
                Auth::user(),
                $incident,
                $validated['priority'],
                $validated['reason'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.incidents.show', $reference)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.incidents.show', $reference)
            ->with('success', "Incident priority adjusted to {$validated['priority']}.");
    }

    /**
     * Confirm unit assignment (Office, Team, Responders).
     */
    public function assign(AssignIncidentRequest $request, string $reference): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('assign', $incident);

        try {
            $this->routingService->assignIncident(
                Auth::user(),
                $incident,
                $request->validated()
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.incidents.show', $reference)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.incidents.show', $reference)
            ->with('success', "Incident {$reference} assigned to designated response unit. Ready for official dispatch authorization.");
    }

    /**
     * Authorize official dispatch and mobilize confirmed assigned units.
     */
    public function dispatch(DispatchIncidentRequest $request, string $reference): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('dispatch', $incident);

        try {
            $this->routingService->dispatchIncident(
                Auth::user(),
                $incident,
                $request->input('dispatch_notes')
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.incidents.show', $reference)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.incidents.show', $reference)
            ->with('success', 'OFFICIAL DISPATCH AUTHORIZED! Assigned responders and reporting citizen notified.');
    }

    /**
     * Record callback verification result.
     */
    public function verifyContact(VerifyContactRequest $request, string $reference): RedirectResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('verifyContact', $incident);

        $this->incidentService->recordContactVerification(
            Auth::user(),
            $incident,
            $request->validated()
        );

        return redirect()->route('admin.incidents.show', $reference)
            ->with('success', 'Caller contact verification record saved.');
    }

    /**
     * JSON endpoint for routing engine evaluation.
     */
    public function routingRecommendation(string $reference): JsonResponse
    {
        $incident = Incident::where('reference_number', $reference)->firstOrFail();
        Gate::authorize('view', $incident);

        $recommendation = $this->routingService->recommendRouting($incident);

        return response()->json([
            'success' => true,
            'rule_matched' => $recommendation['rule_matched'],
            'recommended_office' => $recommendation['recommended_office'] ? [
                'id' => $recommendation['recommended_office']->id,
                'name' => $recommendation['recommended_office']->name,
                'department_type' => $recommendation['recommended_office']->department_type,
            ] : null,
            'recommended_team' => $recommendation['recommended_team'] ? [
                'id' => $recommendation['recommended_team']->id,
                'name' => $recommendation['recommended_team']->team_name,
                'call_sign' => $recommendation['recommended_team']->call_sign,
            ] : null,
            'available_personnel_count' => count($recommendation['eligible_personnel']),
            'offline_personnel_count' => count($recommendation['offline_personnel']),
            'has_offline_warning' => $recommendation['has_offline_warning'],
            'reason' => $recommendation['recommendation_reason'],
        ]);
    }
}
