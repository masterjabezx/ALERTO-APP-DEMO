<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\FacebookMessengerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFacebookMessengerWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
        public ?string $signature = null,
        public ?string $rawContent = null
    ) {}

    public function handle(FacebookMessengerService $service): void
    {
        try {
            $service->processWebhookPayload($this->payload, $this->signature, $this->rawContent);
        } catch (\Throwable $e) {
            Log::error('Error processing queued Meta webhook: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
