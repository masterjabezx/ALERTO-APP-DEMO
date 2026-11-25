<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pwa\SyncIncidentRequest;
use App\Http\Requests\Pwa\SyncPersonnelMilestoneRequest;
use App\Http\Requests\TriggerSosRequest;
use App\Models\SystemSetting;
use App\Services\PwaSyncService;
use App\Services\SosAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PwaController extends Controller
{
    public function __construct(
        protected PwaSyncService $syncService
    ) {}

    /**
     * Connection ping and server healthcheck.
     * Used by client NetworkManager to verify genuine two-way connectivity to Laravel.
     */
    public function ping(Request $request): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'status' => 'online',
            'server_time' => now()->toIso8601String(),
            'authenticated' => $user !== null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'account_type' => $user->account_type,
            ] : null,
        ]);
    }

    /**
     * Render the offline fallback page cached by Service Worker.
     */
    public function offline(): View
    {
        $hotline = SystemSetting::get('dial_daph_command_center_hotline', '0917-123-4567');
        $hotlineTitle = SystemSetting::get('dial_daph_command_center_title', 'DIAL DAPH COMMAND CENTER');

        return view('pwa.offline', [
            'hotline' => $hotline,
            'hotlineTitle' => $hotlineTitle,
        ]);
    }

    /**
     * Synchronize a citizen offline incident draft.
     */
    public function syncIncident(SyncIncidentRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();
        $attachments = $request->file('attachments', []);
        $idempotencyKey = $validated['idempotency_key'];
        $clientUuid = $validated['client_uuid'] ?? null;

        $result = $this->syncService->syncIncidentDraft(
            $user,
            $validated,
            is_array($attachments) ? $attachments : [$attachments],
            $idempotencyKey,
            $clientUuid
        );

        $httpCode = ($result['status'] === 'already_synced') ? 200 : 201;

        return response()->json($result, $httpCode);
    }

    /**
     * Synchronize a queued personnel milestone update.
     */
    public function syncMilestone(SyncPersonnelMilestoneRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $result = $this->syncService->syncPersonnelMilestone(
            $user,
            (int) $validated['assignment_id'],
            $validated['milestone'],
            $validated['notes'] ?? null,
            isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            (int) $validated['expected_version_lock'],
            $validated['idempotency_key']
        );

        if ($result['status'] === 'conflict') {
            return response()->json($result, 409);
        }

        return response()->json($result, 200);
    }

    /**
     * Download authorized offline task cache for field personnel.
     */
    public function tasks(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ($user->account_type !== 'personnel' && ! $user->isAdmin())) {
            return response()->json(['error' => 'Unauthorized access to responder tasks.'], 403);
        }

        $snapshot = $this->syncService->getPersonnelTaskSnapshot($user);

        return response()->json($snapshot);
    }

    /**
     * Download authorized read-only summary for office desk accounts.
     */
    public function office(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ($user->account_type !== 'office' && ! $user->isAdmin())) {
            return response()->json(['error' => 'Unauthorized access to office summaries.'], 403);
        }

        $snapshot = $this->syncService->getOfficeSnapshot($user);

        return response()->json($snapshot);
    }

    /**
     * Report involuntary connectivity loss to update presence to 'connection_lost'.
     */
    public function reportConnectionLost(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            $this->syncService->recordConnectionLoss($user);
        }

        return response()->json([
            'status' => 'acknowledged',
            'presence' => 'connection_lost',
        ]);
    }

    /**
     * Synchronize a queued authenticated Citizen or Personnel SOS alert after connectivity returns.
     */
    public function syncSos(TriggerSosRequest $request, SosAlertService $sosService): JsonResponse
    {
        $user = Auth::user();
        $data = array_merge($request->validated(), ['is_offline_queued' => true]);

        $sos = $user->isPersonnel()
            ? $sosService->createPersonnelSos($user, $data)
            : $sosService->createCitizenOrGuestSos($user, $data);

        return response()->json([
            'status' => 'success',
            'reference_number' => $sos->reference_number,
            'sos_status' => $sos->status,
            'location_status' => $sos->location_status,
            'received_at' => now()->toIso8601String(),
            'message' => 'Queued SOS received by DIAL DAPH Command Center.',
        ], 201);
    }

    /**
     * Synchronize a queued unauthenticated Guest SOS alert after connectivity returns.
     */
    public function syncGuestSos(TriggerSosRequest $request, SosAlertService $sosService): JsonResponse
    {
        $data = array_merge($request->validated(), ['is_offline_queued' => true]);
        $sos = $sosService->createCitizenOrGuestSos(null, $data);

        return response()->json([
            'status' => 'success',
            'reference_number' => $sos->reference_number,
            'sos_status' => $sos->status,
            'location_status' => $sos->location_status,
            'received_at' => now()->toIso8601String(),
            'message' => 'Queued guest SOS received by DIAL DAPH Command Center.',
        ], 201);
    }
}
