<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Citizen\SubmitSimplifiedIncidentRequest;
use App\Http\Requests\TriggerSosRequest;
use App\Models\Incident;
use App\Models\SosAlert;
use App\Services\CitizenIncidentService;
use App\Services\HotlineService;
use App\Services\SosAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestEmergencyController extends Controller
{
    public function __construct(
        protected CitizenIncidentService $incidentService,
        protected SosAlertService $sosService,
        protected HotlineService $hotlineService
    ) {}

    /**
     * Public Guest First-Time Emergency Reporting Form (No Login Required).
     */
    public function showReportForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isCitizen()) {
            return redirect()->route('citizen.dashboard');
        }

        $hotlines = $this->hotlineService->getDualHotlines();

        return view('guest.report', compact('hotlines'));
    }

    /**
     * Submit an emergency incident without logging in (1st Report Guest Flow).
     */
    public function submitReport(SubmitSimplifiedIncidentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $attachments = $request->file('attachments', []);
        $files = is_array($attachments) ? $attachments : ($attachments ? [$attachments] : []);

        $result = $this->incidentService->submitSimplifiedIncident(null, $validated, $files);
        $incident = $result['incident'];

        if (! empty($result['claim_token'])) {
            $request->session()->push('guest_claim_tokens', $result['claim_token']);
        }

        $request->session()->put("guest_confirmed_refs.{$incident->reference_number}", true);
        $request->session()->put('guest_onboarding', [
            'name' => $incident->caller_name,
            'phone_number' => $incident->caller_phone,
            'reference_number' => $incident->reference_number,
        ]);

        return redirect()->route('guest.report.confirmation', ['reference' => $incident->reference_number])
            ->with('success', 'Your emergency report was received by DIAL DAPH Command Center.');
    }

    /**
     * Server-confirmed Guest Emergency Report Confirmation & Citizen Account Onboarding Prompt.
     */
    public function confirmation(Request $request, string $reference): View
    {
        if ($request->session()->get("guest_confirmed_refs.{$reference}") !== true) {
            abort(403, 'This emergency confirmation belongs to another reporting session. Log in as Citizen if you claimed it.');
        }

        $incident = Incident::with(['barangay', 'location', 'evidenceRecords.media'])
            ->where('reference_number', $reference)
            ->firstOrFail();

        $hotlines = $this->hotlineService->getDualHotlines();

        return view('guest.confirmation', compact('incident', 'hotlines'));
    }

    /**
     * Public Guest SOS Confirmation & Emergency Assistance Portal.
     */
    public function showSosForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isCitizen()) {
            return redirect()->route('citizen.dashboard');
        }

        $hotlines = $this->hotlineService->getDualHotlines();

        return view('guest.sos', compact('hotlines'));
    }

    /**
     * Trigger Emergency Guest SOS Alert without requiring authentication.
     */
    public function triggerSos(TriggerSosRequest $request): JsonResponse|RedirectResponse
    {
        $reporter = Auth::check() && Auth::user()->isCitizen() ? Auth::user() : null;
        $sos = $this->sosService->createCitizenOrGuestSos($reporter, $request->validated());

        $request->session()->put("guest_sos_refs.{$sos->reference_number}", true);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'reference_number' => $sos->reference_number,
                'status' => $sos->status,
                'location_status' => $sos->location_status,
                'triggered_at' => $sos->triggered_at?->toIso8601String(),
                'confirmation_url' => route('guest.sos.confirmation', $sos->reference_number),
            ], 201);
        }

        return redirect()->route('guest.sos.confirmation', $sos->reference_number)
            ->with('success', "Emergency SOS {$sos->reference_number} transmitted to DIAL DAPH Command Center!");
    }

    public function sosConfirmation(Request $request, string $reference): View
    {
        $sos = SosAlert::with('barangay')->where('reference_number', $reference)->firstOrFail();

        $isOwner = Auth::check() && (int) $sos->user_id === (int) Auth::id();
        $inSession = $request->session()->get("guest_sos_refs.{$reference}") === true;

        if (! $isOwner && ! $inSession) {
            abort(403, 'Unauthorized SOS confirmation access.');
        }

        $hotlines = $this->hotlineService->getDualHotlines();

        return view('guest.sos-confirmation', compact('sos', 'hotlines'));
    }
}
