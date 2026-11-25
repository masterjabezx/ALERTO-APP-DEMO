<?php

declare(strict_types=1);

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personnel\UpdateLocationRequest;
use App\Http\Requests\TriggerSosRequest;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\Office;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelProfile;
use App\Models\ResponseLog;
use App\Services\IncidentRoutingService;
use App\Services\LiveOperationsMapService;
use App\Services\PersonnelManagementService;
use App\Services\SosAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PersonnelPortalController extends Controller
{
    public function __construct(
        protected PersonnelManagementService $personnelService,
        protected IncidentRoutingService $routingService
    ) {}

    /**
     * 1. Tasks Management: Current active assignments & operational milestone actions.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;

        if (! $personnel) {
            $office = Office::first();
            $personnel = PersonnelProfile::create([
                'user_id' => $user->id,
                'office_id' => $office?->id ?? 1,
                'badge_number' => 'B-'.$user->id.'-'.time(),
                'rank_designation' => 'Field Operative',
            ]);
            $user->load('personnelProfile');
        }

        $availability = $personnel->availability;
        if (! $availability) {
            $availability = PersonnelAvailability::firstOrCreate(
                ['personnel_id' => $personnel->id],
                [
                    'operational_status' => 'available',
                    'status_reason' => 'Dashboard Initialized',
                    'status_updated_at' => now(),
                ]
            );
            $personnel->load('availability');
        }

        // Current active assignments assigned to this responder or their team
        $activeAssignments = IncidentAssignment::with([
            'incident.category',
            'incident.barangay',
            'incident.location',
            'incident.statusHistories',
            'office',
            'team',
        ])
            ->where('status', 'active')
            ->where(function ($q) use ($personnel) {
                $q->where('personnel_id', $personnel->id)
                    ->orWhereIn('team_id', $personnel->teams->pluck('id') ?? []);
            })
            ->latest('assigned_at')
            ->get();

        return view('dashboards.personnel', [
            'user' => $user,
            'personnel' => $personnel,
            'availability' => $availability,
            'activeAssignments' => $activeAssignments,
        ]);
    }

    /**
     * 2. Active Incidents within authorized jurisdiction.
     */
    public function activeIncidents(): View
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;

        $authorizedBarangays = $personnel?->jurisdictions->pluck('id') ?? collect();

        $query = Incident::with(['category', 'barangay', 'location', 'activeAssignments.office', 'activeAssignments.team'])
            ->whereIn('status', ['accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress']);

        // Scope to jurisdiction if assigned
        if ($authorizedBarangays->isNotEmpty()) {
            $query->whereIn('barangay_id', $authorizedBarangays);
        }

        $incidents = $query->latest('submitted_at')->paginate(10);

        return view('personnel.active-incidents', [
            'user' => $user,
            'personnel' => $personnel,
            'incidents' => $incidents,
        ]);
    }

    /**
     * 3. Live Incident Map displaying permitted scenes & responder position.
     */
    public function map(): View
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;

        $authorizedBarangays = $personnel?->jurisdictions->pluck('id') ?? collect();

        $query = Incident::with(['category', 'barangay', 'location'])
            ->whereIn('status', ['accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress']);

        if ($authorizedBarangays->isNotEmpty()) {
            $query->whereIn('barangay_id', $authorizedBarangays);
        }

        $activeIncidents = $query->get();
        $latestLocation = $personnel?->locations()->latest('recorded_at')->first();

        return view('personnel.map', [
            'user' => $user,
            'personnel' => $personnel,
            'activeIncidents' => $activeIncidents,
            'latestLocation' => $latestLocation,
        ]);
    }

    /**
     * JSON Endpoint for Personnel Map data.
     */
    public function mapData(Request $request): JsonResponse
    {
        $mapService = app(LiveOperationsMapService::class);
        $data = $mapService->getPersonnelMapData(Auth::user(), $request->all());

        return response()->json($data);
    }

    /**
     * 4. Records & Incidents history.
     */
    public function records(): View
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;

        $completedAssignments = IncidentAssignment::with(['incident.category', 'incident.barangay', 'team', 'office'])
            ->where(function ($q) use ($personnel) {
                $q->where('personnel_id', $personnel?->id)
                    ->orWhereIn('team_id', $personnel?->teams->pluck('id') ?? []);
            })
            ->latest('assigned_at')
            ->paginate(15);

        return view('personnel.records', [
            'user' => $user,
            'personnel' => $personnel,
            'assignments' => $completedAssignments,
        ]);
    }

    /**
     * Advance tactical milestone status (Acknowledged, On the Way, On Scene, In Progress, Resolved).
     */
    public function updateMilestone(Request $request, int $assignmentId): RedirectResponse
    {
        $assignment = IncidentAssignment::with('incident')->findOrFail($assignmentId);
        $user = Auth::user();
        $personnel = $user->personnelProfile;

        if (! $personnel) {
            abort(403, 'Personnel profile required to execute response milestones.');
        }

        $teamIds = $personnel->teams()->pluck('response_teams.id')->toArray();
        $isAssigned = ((int) $assignment->personnel_id === (int) $personnel->id)
            || ($assignment->team_id !== null && in_array((int) $assignment->team_id, $teamIds, true));

        if (! $isAssigned) {
            abort(403, 'Unauthorized: You are not assigned to this emergency task.');
        }

        if ($assignment->status !== 'active' || ! $assignment->incident) {
            return back()->with('error', 'This assignment is no longer active.');
        }

        $request->validate([
            'milestone' => ['required', 'in:acknowledged,on_the_way,on_scene,in_progress,resolved'],
            'notes' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $milestone = $request->input('milestone');
        $notes = $request->input('notes');

        try {
            DB::transaction(function () use ($assignment, $user, $personnel, $milestone, $notes, $request): void {
                // Advance and validate incident status transition first
                $this->routingService->advanceMilestoneStatus(
                    $user,
                    $assignment->incident,
                    $milestone,
                    $notes ?? "Responder {$user->name} reached milestone: {$milestone}."
                );

                // Record in response_logs (enum uses 'cleared' when incident reaches 'resolved')
                $logMilestone = $milestone === 'resolved' ? 'cleared' : $milestone;
                ResponseLog::create([
                    'incident_id' => $assignment->incident_id,
                    'assignment_id' => $assignment->id,
                    'personnel_id' => $personnel->id,
                    'milestone' => $logMilestone,
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'notes' => $notes,
                    'recorded_at' => now(),
                ]);

                // Update assignment tracking timestamps/status
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
            });
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Response status updated to: {$milestone}.");
    }

    /**
     * Broadcast live duty GPS coordinates.
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;

        if (! $personnel) {
            return response()->json(['error' => 'Personnel profile not found'], 404);
        }

        $validated = $request->validated();

        $location = $this->personnelService->recordPersonnelLocation(
            $personnel,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            isset($validated['speed']) ? (float) $validated['speed'] : null,
            isset($validated['heading']) ? (float) $validated['heading'] : null
        );

        return response()->json([
            'success' => true,
            'recorded_at' => $location->recorded_at->toIso8601String(),
        ]);
    }

    /**
     * Trigger Personnel Distress SOS Alert to DIAL DAPH Command Center & LDRRMO Super Admin.
     */
    public function triggerSos(TriggerSosRequest $request, SosAlertService $sosService): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $sos = $sosService->createPersonnelSos($user, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'reference_number' => $sos->reference_number,
                'status' => $sos->status,
                'location_status' => $sos->location_status,
                'triggered_at' => $sos->triggered_at?->toIso8601String(),
            ], 201);
        }

        return back()->with('success', "PERSONNEL DISTRESS SOS {$sos->reference_number} SENT TO COMMAND CENTER!");
    }

    /**
     * Keep-alive presence heartbeat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $this->personnelService->recordHeartbeat(Auth::user(), $request->input('device'));

        return response()->json(['success' => true]);
    }
}
