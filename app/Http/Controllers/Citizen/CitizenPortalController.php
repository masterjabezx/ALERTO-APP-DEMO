<?php

declare(strict_types=1);

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\SubmitSimplifiedIncidentRequest;
use App\Http\Requests\Citizen\UpdateCitizenProfileRequest;
use App\Http\Requests\TriggerSosRequest;
use App\Models\EmergencyHotline;
use App\Models\SosAlert;
use App\Models\SystemSetting;
use App\Services\CitizenIncidentService;
use App\Services\HotlineService;
use App\Services\SosAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitizenPortalController extends Controller
{
    public function __construct(
        private readonly CitizenIncidentService $incidentService,
        private readonly HotlineService $hotlineService,
        private readonly SosAlertService $sosService
    ) {}

    /**
     * Citizen Dashboard / Simplified Emergency Reporting + SOS view.
     */
    public function index(): View
    {
        $user = Auth::user();
        $hotlines = $this->hotlineService->getDualHotlines();
        $primaryAddress = $user->citizenAddresses()->where('is_primary', true)->first();
        $activeSos = SosAlert::where('user_id', $user->id)
            ->active()
            ->latest('triggered_at')
            ->first();

        return view('dashboards.citizen', [
            'user' => $user,
            'hotlines' => $hotlines,
            'hotline' => $hotlines['dial_daph']['number'],
            'hotlineTitle' => $hotlines['dial_daph']['title'],
            'primaryAddress' => $primaryAddress,
            'activeSos' => $activeSos,
        ]);
    }

    /**
     * Process simplified incident submission from authenticated citizen.
     */
    public function submitIncident(SubmitSimplifiedIncidentRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validated();
        $attachments = $request->file('attachments', []);

        $result = $this->incidentService->submitSimplifiedIncident(
            $user,
            $validated,
            is_array($attachments) ? $attachments : ($attachments ? [$attachments] : [])
        );

        return redirect()->route('citizen.incident.success', ['reference' => $result['incident']->reference_number])
            ->with('success', 'Emergency report received by DIAL DAPH Command Center! Keep your phone nearby for call verification.');
    }

    /**
     * Allow Citizen to update editable display name and contact phone number.
     */
    public function updateProfile(UpdateCitizenProfileRequest $request): RedirectResponse
    {
        $this->incidentService->updateCitizenContactProfile(Auth::user(), $request->validated());

        return back()->with('success', 'Your Citizen emergency contact details have been updated.');
    }

    /**
     * Trigger Emergency Citizen SOS Alert.
     */
    public function triggerSos(TriggerSosRequest $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $sos = $this->sosService->createCitizenOrGuestSos($user, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'reference_number' => $sos->reference_number,
                'status' => $sos->status,
                'location_status' => $sos->location_status,
                'triggered_at' => $sos->triggered_at?->toIso8601String(),
            ], 201);
        }

        return back()->with('success', "EMERGENCY SOS {$sos->reference_number} SENT TO DIAL DAPH COMMAND CENTER!");
    }

    /**
     * Successful incident report confirmation screen.
     */
    public function submissionSuccess(string $reference): View|RedirectResponse
    {
        $user = Auth::user();
        $incident = $this->incidentService->getCitizenIncident($user, $reference);

        if (! $incident) {
            return redirect()->route('citizen.dashboard')->with('error', 'Incident record not found.');
        }

        $hotlines = $this->hotlineService->getDualHotlines();

        return view('citizen.success', [
            'incident' => $incident,
            'hotlines' => $hotlines,
            'hotline' => $hotlines['dial_daph']['number'],
            'hotlineTitle' => $hotlines['dial_daph']['title'],
        ]);
    }

    /**
     * Incident Record list with status filtering.
     */
    public function records(Request $request): View
    {
        $user = Auth::user();
        $filter = $request->query('status', 'all');
        $incidents = $this->incidentService->getCitizenIncidentsList($user, $filter);
        $hotlines = $this->hotlineService->getDualHotlines();

        return view('citizen.records', [
            'user' => $user,
            'incidents' => $incidents,
            'currentFilter' => $filter,
            'hotlines' => $hotlines,
            'hotline' => $hotlines['dial_daph']['number'],
        ]);
    }

    /**
     * Show detailed authorized incident record view with status history timeline.
     */
    public function showRecord(string $reference): View|RedirectResponse
    {
        $user = Auth::user();
        $incident = $this->incidentService->getCitizenIncident($user, $reference);

        if (! $incident) {
            abort(404, 'Incident record not found or access unauthorized.');
        }

        $hotlines = $this->hotlineService->getDualHotlines();

        return view('citizen.record-detail', [
            'incident' => $incident,
            'hotlines' => $hotlines,
            'hotline' => $hotlines['dial_daph']['number'],
        ]);
    }

    /**
     * Contact Numbers directory page.
     */
    public function contacts(): View
    {
        $hotlines = $this->hotlineService->getDualHotlines();
        $facebookUrl = SystemSetting::get('official_facebook_page_url', 'https://facebook.com/hinobaan.ldrmo');
        $additionalHotlines = EmergencyHotline::orderBy('sort_order')->get();

        return view('citizen.contacts', [
            'hotlines' => $hotlines,
            'hotline' => $hotlines['dial_daph']['number'],
            'hotlineTitle' => $hotlines['dial_daph']['title'],
            'facebookUrl' => $facebookUrl,
            'additionalHotlines' => $additionalHotlines,
        ]);
    }
}
