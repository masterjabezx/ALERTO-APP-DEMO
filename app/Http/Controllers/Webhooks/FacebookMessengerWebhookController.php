<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFacebookMessengerWebhookJob;
use App\Models\FbIntegrationSetting;
use App\Services\FacebookMessengerService;
use App\Services\MetaMessengerApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class FacebookMessengerWebhookController extends Controller
{
    public function __construct(
        protected MetaMessengerApiService $apiService,
        protected FacebookMessengerService $messengerService
    ) {}

    /**
     * Handle Meta Webhook Verification Challenge (GET /webhooks/facebook/messenger).
     */
    public function verify(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = config('services.meta.verify_token');
        if (empty($expectedToken)) {
            $setting = FbIntegrationSetting::first();
            $expectedToken = $setting?->verify_token;
        }

        if ($mode === 'subscribe' && ! empty($expectedToken) && ! empty($token) && hash_equals((string) $expectedToken, (string) $token)) {
            Log::info('Meta Webhook Challenge Verified Successfully.');

            FbIntegrationSetting::query()->update([
                'webhook_verified' => true,
                'last_webhook_at' => now(),
            ]);

            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta Webhook Verification Challenge Failed.', [
            'received_token' => $token,
            'mode' => $mode,
        ]);

        return response()->json(['error' => 'Verification token mismatch or invalid mode.'], 403);
    }

    /**
     * Receive and process incoming Meta Webhook Event (POST /webhooks/facebook/messenger).
     */
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256');
        $rawContent = $request->getContent();

        // Enforce HMAC-SHA256 signature verification whenever header is provided or outside isolated testing
        if ($signature !== null || ! App::environment('testing')) {
            if (! $this->apiService->verifyWebhookSignature($rawContent, $signature)) {
                Log::warning('Meta Webhook Rejected: Invalid or missing X-Hub-Signature-256.');

                return response()->json(['error' => 'Invalid signature.'], 403);
            }
        }

        $payload = $request->all();

        // In testing environment or sync processing, process synchronously for immediate assertion
        if (App::environment('testing')) {
            $this->messengerService->processWebhookPayload($payload, $signature, $rawContent);
        } else {
            ProcessFacebookMessengerWebhookJob::dispatch($payload, $signature, $rawContent);
        }

        return response()->json(['status' => 'EVENT_RECEIVED'], 200);
    }
}
