<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaMessengerApiService
{
    protected string $graphVersion;

    protected ?string $pageAccessToken;

    protected ?string $appSecret;

    public function __construct()
    {
        $this->graphVersion = (string) config('services.meta.graph_version', 'v22.0');
        $this->pageAccessToken = config('services.meta.page_access_token');
        $this->appSecret = config('services.meta.webhook_secret') ?: config('services.meta.app_secret');
    }

    /**
     * Verify HMAC-SHA256 signature from Meta webhook request.
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || trim($signatureHeader) === '') {
            return false;
        }

        $secret = $this->appSecret;
        if (empty($secret)) {
            Log::warning('Meta App Secret is not configured. Webhook signature cannot be strictly verified.');

            return false;
        }

        // Meta signature format: "sha256=<hash>"
        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signatureHeader, 7);
        $calculatedHash = hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($expectedHash, $calculatedHash);
    }

    /**
     * Send plain text message to a Facebook Page-Scoped User (PSID).
     *
     * @return array<string, mixed>
     */
    public function sendTextMessage(string $recipientPsid, string $text, ?string $tag = null): array
    {
        $payload = [
            'recipient' => ['id' => $recipientPsid],
            'message' => ['text' => $text],
        ];

        if ($tag !== null) {
            $payload['messaging_type'] = 'MESSAGE_TAG';
            $payload['tag'] = $tag;
        } else {
            $payload['messaging_type'] = 'RESPONSE';
        }

        return $this->callMessagesApi($payload);
    }

    /**
     * Send message with quick reply chips to guide citizen incident reporting.
     *
     * @param  array<int, array{content_type?: string, title: string, payload: string}>  $quickReplies
     * @return array<string, mixed>
     */
    public function sendQuickReply(string $recipientPsid, string $text, array $quickReplies): array
    {
        $formattedReplies = array_map(function ($qr) {
            return [
                'content_type' => $qr['content_type'] ?? 'text',
                'title' => $qr['title'],
                'payload' => $qr['payload'],
            ];
        }, $quickReplies);

        $payload = [
            'recipient' => ['id' => $recipientPsid],
            'messaging_type' => 'RESPONSE',
            'message' => [
                'text' => $text,
                'quick_replies' => $formattedReplies,
            ],
        ];

        return $this->callMessagesApi($payload);
    }

    /**
     * Send structured button template.
     *
     * @param  array<int, array<string, mixed>>  $buttons
     * @return array<string, mixed>
     */
    public function sendButtonTemplate(string $recipientPsid, string $text, array $buttons): array
    {
        $payload = [
            'recipient' => ['id' => $recipientPsid],
            'messaging_type' => 'RESPONSE',
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $text,
                        'buttons' => $buttons,
                    ],
                ],
            ],
        ];

        return $this->callMessagesApi($payload);
    }

    /**
     * Execute Graph API request to send message.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function callMessagesApi(array $body): array
    {
        $token = $this->pageAccessToken;

        // If no token is configured (e.g. initial setup or test mock mode)
        if (empty($token)) {
            Log::info('Meta Page Access Token not configured. Simulated outbound response recorded.', [
                'recipient' => $body['recipient']['id'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'simulated' => true,
                'message_id' => 'sim_'.uniqid(),
                'recipient_id' => $body['recipient']['id'] ?? '',
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphVersion}/me/messages";

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($url, $body);

            if ($response->successful()) {
                return array_merge(['success' => true], $response->json());
            }

            $errorData = $response->json('error') ?? [];
            $errorMessage = $errorData['message'] ?? 'Meta API request failed with status '.$response->status();

            Log::error('Meta Graph API Send Error', [
                'status' => $response->status(),
                'code' => $errorData['code'] ?? null,
                'subcode' => $errorData['error_subcode'] ?? null,
                'message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'code' => $errorData['code'] ?? $response->status(),
                'subcode' => $errorData['error_subcode'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Meta Graph API Network Exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Network error connecting to Meta Graph API.',
            ];
        }
    }
}
