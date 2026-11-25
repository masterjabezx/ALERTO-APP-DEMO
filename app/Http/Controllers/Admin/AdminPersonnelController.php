<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePersonnelRequest;
use App\Http\Requests\Admin\StoreResponseBaseRequest;
use App\Http\Requests\Admin\StoreResponseTeamRequest;
use App\Http\Requests\Admin\UpdatePersonnelRequest;
use App\Models\Barangay;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\ResponseBase;
use App\Models\ResponseTeam;
use App\Services\PersonnelManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPersonnelController extends Controller
{
    public function __construct(
        protected PersonnelManagementService $personnelService
    ) {}

    /**
     * 1. Personnel Directory with Search, Filters & KPIs.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'office_id', 'team_id', 'operational_status', 'is_active']);
        $personnel = $this->personnelService->getFilteredPersonnel($filters, 15);
        $stats = $this->personnelService->getAvailabilityMonitoringStats();

        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $teams = ResponseTeam::where('is_active', true)->orderBy('team_name')->get();

        return view('admin.personnel.index', [
            'personnel' => $personnel,
            'stats' => $stats,
            'offices' => $offices,
            'teams' => $teams,
            'filters' => $filters,
        ]);
    }

    /**
     * 2. Add Personnel Form.
     */
    public function create(): View
    {
        $offices = Office::where('is_active', true)->with('teams')->orderBy('name')->get();
        $teams = ResponseTeam::where('is_active', true)->orderBy('team_name')->get();
        $barangays = Barangay::orderBy('name')->get();
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.personnel.create', [
            'offices' => $offices,
            'teams' => $teams,
            'barangays' => $barangays,
            'categories' => $categories,
        ]);
    }

    /**
     * Store newly created personnel.
     */
    public function store(StorePersonnelRequest $request): RedirectResponse
    {
        $profile = $this->personnelService->createPersonnel(
            $request->validated(),
            Auth::user()
        );

        return redirect()->route('admin.personnel.index')
            ->with('success', "Personnel account #{$profile->badge_number} successfully registered.");
    }

    /**
     * Edit Personnel Profile.
     */
    public function edit(int $id): View
    {
        $profile = PersonnelProfile::with(['user', 'teams', 'jurisdictions'])->findOrFail($id);
        $offices = Office::where('is_active', true)->with('teams')->orderBy('name')->get();
        $teams = ResponseTeam::where('is_active', true)->orderBy('team_name')->get();
        $barangays = Barangay::orderBy('name')->get();
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.personnel.edit', [
            'profile' => $profile,
            'offices' => $offices,
            'teams' => $teams,
            'barangays' => $barangays,
            'categories' => $categories,
        ]);
    }

    /**
     * Update personnel profile.
     */
    public function update(UpdatePersonnelRequest $request, int $id): RedirectResponse
    {
        $profile = PersonnelProfile::findOrFail($id);
        $this->personnelService->updatePersonnel($profile, $request->validated(), Auth::user());

        return redirect()->route('admin.personnel.index')
            ->with('success', "Personnel profile #{$profile->badge_number} updated.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $profile = PersonnelProfile::findOrFail($id);
        $newStatus = ! $profile->user->is_active;

        $this->personnelService->togglePersonnelStatus($profile, $newStatus, Auth::user());

        $msg = $newStatus ? "Account #{$profile->badge_number} activated." : "Account #{$profile->badge_number} deactivated and placed offline.";

        return back()->with('success', $msg);
    }

    /**
     * Reset personnel password.
     */
    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);
        $profile = PersonnelProfile::findOrFail($id);

        $this->personnelService->resetPersonnelPassword($profile, $request->input('password'), Auth::user());

        return back()->with('success', "Password reset successfully for badge #{$profile->badge_number}.");
    }

    /**
     * 3 & 4. Response Teams Management.
     */
    public function teams(Request $request): View
    {
        $teams = ResponseTeam::with(['office', 'base', 'leader.user', 'members.user', 'barangays'])
            ->latest('id')
            ->get();

        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $bases = ResponseBase::where('is_active', true)->orderBy('base_name')->get();
        $allPersonnel = PersonnelProfile::with('user')->get();
        $barangays = Barangay::orderBy('name')->get();
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.personnel.teams', [
            'teams' => $teams,
            'offices' => $offices,
            'bases' => $bases,
            'allPersonnel' => $allPersonnel,
            'barangays' => $barangays,
            'categories' => $categories,
        ]);
    }

    public function storeTeam(StoreResponseTeamRequest $request): RedirectResponse
    {
        $team = $this->personnelService->createTeam($request->validated());

        return redirect()->route('admin.personnel.teams')
            ->with('success', "Response Team '{$team->team_name}' ({$team->call_sign}) created successfully.");
    }

    public function updateTeam(Request $request, int $id): RedirectResponse
    {
        $team = ResponseTeam::findOrFail($id);
        $validated = $request->validate([
            'office_id' => ['sometimes', 'required', 'exists:offices,id'],
            'team_name' => ['sometimes', 'required', 'string', 'max:128'],
            'call_sign' => ['sometimes', 'required', 'string', 'max:64', 'unique:response_teams,call_sign,'.$team->id],
            'team_type' => ['sometimes', 'required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'response_base_id' => ['nullable', 'exists:response_bases,id'],
            'team_leader_id' => ['nullable', 'exists:personnel_profiles,id'],
            'authorized_categories' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->personnelService->updateTeam($team, $validated);

        return redirect()->route('admin.personnel.teams')
            ->with('success', "Response Team '{$team->team_name}' updated.");
    }

    /**
     * 5. Personnel Jurisdictions Management.
     */
    public function jurisdictions(Request $request): View
    {
        $barangays = Barangay::with(['personnelJurisdictions.personnel.user', 'teamJurisdictions.team'])->get();
        $personnel = PersonnelProfile::with(['user', 'jurisdictions'])->get();
        $teams = ResponseTeam::with('barangays')->get();

        return view('admin.personnel.jurisdictions', [
            'barangays' => $barangays,
            'personnel' => $personnel,
            'teams' => $teams,
        ]);
    }

    /**
     * 6. Availability Monitoring.
     */
    public function availability(Request $request): View
    {
        // Auto-check connection loss for stale heartbeats
        $this->personnelService->checkConnectionLoss();

        $stats = $this->personnelService->getAvailabilityMonitoringStats();
        $activePersonnel = PersonnelProfile::with(['user.userPresence', 'office', 'availability', 'teams'])
            ->get();

        return view('admin.personnel.availability', [
            'stats' => $stats,
            'personnel' => $activePersonnel,
        ]);
    }

    /**
     * 7. Active Personnel Assignments.
     */
    public function assignments(Request $request): View
    {
        $activeAssignments = IncidentAssignment::with([
            'incident.category',
            'incident.barangay',
            'office',
            'team',
            'personnel.user',
            'dispatcher',
        ])
            ->where('status', 'active')
            ->latest('assigned_at')
            ->paginate(15);

        return view('admin.personnel.assignments', [
            'assignments' => $activeAssignments,
        ]);
    }

    /**
     * 11. Response Bases Management.
     */
    public function bases(Request $request): View
    {
        $bases = ResponseBase::with(['office', 'barangay', 'teams'])->latest('id')->get();
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();

        return view('admin.personnel.bases', [
            'bases' => $bases,
            'offices' => $offices,
            'barangays' => $barangays,
        ]);
    }

    public function storeBase(StoreResponseBaseRequest $request): RedirectResponse
    {
        $base = $this->personnelService->createBase($request->validated());

        return redirect()->route('admin.personnel.bases')
            ->with('success', "Response Base '{$base->base_name}' added.");
    }
}
