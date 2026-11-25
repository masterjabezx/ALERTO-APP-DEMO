<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\ResponseTeam;
use App\Models\SosAlert;
use App\Services\HotlineService;
use App\Services\SosAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSosController extends Controller
{
    public function __construct(
        protected SosAlertService $sosService,
        protected HotlineService $hotlineService
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'active');

        $query = SosAlert::with([
            'user',
            'personnel.user',
            'office',
            'barangay',
            'acknowledger',
            'assignments.office',
            'assignments.team',
            'assignments.personnel.user',
            'logs.actor',
        ])->latest('triggered_at');

        match ($tab) {
            'active' => $query->active(),
            'citizen' => $query->whereIn('source_type', ['citizen', 'guest_citizen']),
            'personnel' => $query->where('source_type', 'personnel'),
            'resolved' => $query->whereIn('status', ['resolved', 'converted_to_incident', 'cancelled']),
            default => null,
        };

        $alerts = $query->paginate(15)->withQueryString();
        $offices = Office::where('is_active', true)->with('teams')->orderBy('name')->get();
        $personnelList = PersonnelProfile::with(['user', 'office', 'availability'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        $counts = [
            'active' => SosAlert::active()->count(),
            'citizen' => SosAlert::active()->whereIn('source_type', ['citizen', 'guest_citizen'])->count(),
            'personnel' => SosAlert::active()->where('source_type', 'personnel')->count(),
            'resolved' => SosAlert::whereIn('status', ['resolved', 'converted_to_incident', 'cancelled'])->count(),
        ];

        return view('admin.sos.index', compact('alerts', 'offices', 'personnelList', 'tab', 'counts'));
    }

    public function show(int $id): View
    {
        $sos = SosAlert::with([
            'user',
            'personnel.user',
            'office',
            'barangay',
            'acknowledger',
            'resolver',
            'convertedIncident',
            'assignments.office',
            'assignments.team',
            'assignments.personnel.user',
            'logs.actor',
        ])->findOrFail($id);

        $offices = Office::where('is_active', true)->with('teams')->orderBy('name')->get();
        $teams = ResponseTeam::where('is_active', true)->with('office')->orderBy('team_name')->get();
        $personnelList = PersonnelProfile::with(['user', 'office', 'availability'])->get();
        $telHref = $this->hotlineService->sanitizeTelUri($sos->sender_phone);

        return view('admin.sos.show', compact('sos', 'offices', 'teams', 'personnelList', 'telHref'));
    }

    public function acknowledge(Request $request, int $id): RedirectResponse
    {
        $sos = SosAlert::findOrFail($id);
        $this->sosService->acknowledgeSos(Auth::user(), $sos, $request->input('notes'));

        return back()->with('success', "SOS alert {$sos->reference_number} acknowledged.");
    }

    public function callAttempt(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'outcome' => ['required', 'in:answered,no_answer,busy,invalid_number,callback_required'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sos = SosAlert::findOrFail($id);
        $this->sosService->recordCallAttempt(Auth::user(), $sos, $request->input('outcome'), $request->input('notes'));

        return back()->with('success', "SOS call attempt logged for {$sos->reference_number}.");
    }

    public function assign(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'team_id' => ['nullable', 'integer', 'exists:response_teams,id'],
            'personnel_id' => ['nullable', 'integer', 'exists:personnel_profiles,id'],
            'use_routing_rule' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sos = SosAlert::findOrFail($id);
        $result = $this->sosService->assignSosResponders(Auth::user(), $sos, $request->all());

        if ($result['warning']) {
            return back()->with('warning', $result['warning']);
        }

        return back()->with('success', "Response dispatched for SOS {$sos->reference_number}.");
    }

    public function escalate(Request $request, int $id): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $sos = SosAlert::findOrFail($id);
        $this->sosService->escalateSos(Auth::user(), $sos, $request->input('reason'));

        return back()->with('success', "SOS {$sos->reference_number} escalated to LDRRMO Super Admin.");
    }

    public function resolve(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:resolved,closed,cancelled'],
            'resolution_notes' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $sos = SosAlert::findOrFail($id);
        $this->sosService->resolveSos(Auth::user(), $sos, $request->input('status'), $request->input('resolution_notes'));

        return back()->with('success', "SOS {$sos->reference_number} marked {$request->input('status')}.");
    }

    public function convert(int $id): RedirectResponse
    {
        $sos = SosAlert::findOrFail($id);
        $incident = $this->sosService->convertSosToIncident(Auth::user(), $sos);

        $routeName = Auth::user()->isSuperAdmin() ? 'super-admin.incidents.show' : 'admin.incidents.show';

        return redirect()->route($routeName, $incident->reference_number)
            ->with('success', "SOS {$sos->reference_number} converted to Incident {$incident->reference_number}.");
    }
}
