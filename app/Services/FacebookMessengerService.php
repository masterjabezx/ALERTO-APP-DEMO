<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\FbMessageReceived;
use App\Events\IncidentIntakeUpdated;
use App\Models\Barangay;
use App\Models\FbAttachment;
use App\Models\FbConversation;
use App\Models\FbIntegrationSetting;
use App\Models\FbMessage;
use App\Models\FbWebhookEvent;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentContactVerification;
use App\Models\IncidentIntakeRecord;
use App\Models\IncidentLocation;
use App\Models\MediaGeoMetadata;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookMessengerService
{
    public function __construct(
        protected MetaMessengerApiService $apiService
    ) {}

    /**
     * Process an incoming Meta webhook event payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhookPayload(array $payload, ?string $signature = null, ?string $rawContent = null): void
    {
        // 1. Signature verification if signature provided
        if ($signature !== null && $rawContent !== null) {
            if (! $this->apiService->verifyWebhookSignature($rawContent, $signature)) {
                throw new \InvalidArgumentException('Invalid Meta webhook signature.');
            }
        }

        // Verify top-level object is page
        if (($payload['object'] ?? '') !== 'page') {
            return;
        }

        // Update settings last webhook timestamp
        FbIntegrationSetting::query()->update([
            'last_webhook_at' => now(),
            'webhook_verified' => true,
        ]);

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $pageId = (string) ($entry['id'] ?? '');
            $messagingEvents = $entry['messaging'] ?? [];

            foreach ($messagingEvents as $event) {
                $senderPsid = (string) ($event['sender']['id'] ?? '');
                $timestamp = isset($event['timestamp']) ? (int) $event['timestamp'] : null;

                // Event deduplication identifier
                $eventId = $event['message']['mid'] ?? ($event['postback']['mid'] ?? ($pageId.'_'.$senderPsid.'_'.$timestamp));

                // Deduplicate webhook events
                $existing = FbWebhookEvent::where('event_id', $eventId)->first();
                if ($existing && $existing->processed) {
                    continue;
                }

                FbWebhookEvent::firstOrCreate(
                    ['event_id' => $eventId],
                    ['processed' => true, 'created_at' => now()]
                );

                if (isset($event['message'])) {
                    $this->handleIncomingMessage($pageId, $senderPsid, $event['message'], $timestamp);
                } elseif (isset($event['postback'])) {
                    $this->handlePostback($pageId, $senderPsid, $event['postback']);
                }
            }
        }
    }

    /**
     * Handle an incoming Facebook message.
     *
     * @param  array<string, mixed>  $messageData
     */
    public function handleIncomingMessage(string $pageId, string $psid, array $messageData, ?int $timestamp = null): FbMessage
    {
        $mid = (string) ($messageData['mid'] ?? ('mid_'.uniqid()));

        // Check message deduplication
        $existingMessage = FbMessage::where('fb_message_mid', $mid)->first();
        if ($existingMessage) {
            return $existingMessage;
        }

        /** @var FbConversation $conversation */
        $conversation = FbConversation::firstOrCreate(
            ['fb_user_psid' => $psid],
            [
                'page_id' => $pageId,
                'last_interaction_at' => now(),
                'unread_count' => 0,
            ]
        );

        $text = $messageData['text'] ?? null;
        $quickReplyPayload = $messageData['quick_reply']['payload'] ?? null;

        $sentAt = $timestamp !== null
            ? Carbon::createFromTimestampMs($timestamp)
            : now();

        $message = FbMessage::create([
            'fb_conversation_id' => $conversation->id,
            'fb_message_mid' => $mid,
            'direction' => 'incoming',
            'message_text' => $text,
            'raw_payload' => $messageData,
            'delivery_status' => 'delivered',
            'sent_at' => $sentAt,
        ]);

        // Process attachments
        if (! empty($messageData['attachments'])) {
            $this->processAttachments($message, $messageData['attachments']);
        }

        // Triage emergency keywords in text
        $isEmergency = $this->detectEmergencyKeywords($text ?? '');
        if ($isEmergency) {
            $conversation->is_possible_incident = true;
        }

        $conversation->increment('unread_count');
        $conversation->update([
            'last_interaction_at' => now(),
            'latest_message_text' => Str::limit($text ?? '[Attachment]', 255),
        ]);

        // Update settings last message timestamp
        FbIntegrationSetting::query()->update(['last_message_at' => now()]);

        // Run automated reporting assistant
        $this->processReportingAssistant($conversation, $text ?? '', $quickReplyPayload, $messageData['attachments'] ?? []);

        // Broadcast real-time event to Admin operations
        $message->load('conversation');
        FbMessageReceived::dispatch($message);

        return $message;
    }

    /**
     * Handle a user postback button click.
     *
     * @param  array<string, mixed>  $postbackData
     */
    public function handlePostback(string $pageId, string $psid, array $postbackData): void
    {
        $payload = $postbackData['payload'] ?? '';
        $title = $postbackData['title'] ?? 'Selected Option';

        $this->handleIncomingMessage($pageId, $psid, [
            'mid' => 'postback_'.uniqid(),
            'text' => $title,
            'quick_reply' => ['payload' => $payload],
        ]);
    }

    /**
     * Automated Messenger Reporting Assistant state machine.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function processReportingAssistant(
        FbConversation $conversation,
        string $text,
        ?string $quickReplyPayload = null,
        array $attachments = []
    ): void {
        $intake = IncidentIntakeRecord::where('fb_conversation_id', $conversation->id)
            ->whereNotIn('status', ['converted', 'rejected'])
            ->latest('id')
            ->first();

        if (! $intake) {
            $intake = IncidentIntakeRecord::create([
                'fb_conversation_id' => $conversation->id,
                'current_step' => 'awaiting_action',
                'status' => 'new',
                'is_complete' => false,
            ]);
        }

        // If user tapped specific menu action or quick reply
        if ($quickReplyPayload === 'REPORT_INCIDENT' || Str::contains(strtolower($text), ['report', 'incident', 'emergency', 'help', 'saklolo'])) {
            $intake->update([
                'current_step' => 'awaiting_category',
                'status' => 'incomplete',
            ]);

            $categories = IncidentCategory::limit(6)->get();
            $replies = $categories->map(fn ($c) => [
                'title' => Str::limit($c->name, 20),
                'payload' => 'CAT_'.$c->id,
            ])->toArray();

            $this->apiService->sendQuickReply(
                $conversation->fb_user_psid,
                '🚨 Hinoba-an Emergency Intake: What type of emergency incident are you reporting?',
                $replies
            );

            return;
        }

        if ($quickReplyPayload === 'REPORTING_INSTRUCTIONS') {
            $this->apiService->sendButtonTemplate(
                $conversation->fb_user_psid,
                "📋 ALERTO Emergency Reporting Instructions:\n\n1. Select your emergency category.\n2. State exact location/barangay.\n3. Provide a valid 11-digit mobile number.\n4. Send photos or videos if safe to do so.",
                [
                    [
                        'type' => 'postback',
                        'title' => '🚨 Report an Incident',
                        'payload' => 'REPORT_INCIDENT',
                    ],
                ]
            );

            return;
        }

        if ($quickReplyPayload === 'CONTACT_COMMAND_CENTER') {
            $this->apiService->sendTextMessage(
                $conversation->fb_user_psid,
                "📞 Hinoba-an LDRRMO Central Command Hotlines:\n\n• Emergency Mobile: 0917-123-4567\n• Landline: (034) 123-4567\n• VHF Radio: Channel 146.500 MHz\n\nOfficers are standing by 24/7."
            );

            return;
        }

        // Process location attachment if sent
        foreach ($attachments as $att) {
            if (($att['type'] ?? '') === 'location' && isset($att['payload']['coordinates'])) {
                $lat = (float) $att['payload']['coordinates']['lat'];
                $lng = (float) $att['payload']['coordinates']['long'];

                $intake->update([
                    'collected_latitude' => $lat,
                    'collected_longitude' => $lng,
                    'location_source' => 'messenger_gps',
                ]);
            }
        }

        // State Machine Steps
        if (str_starts_with($quickReplyPayload ?? '', 'CAT_')) {
            $catId = (int) substr((string) $quickReplyPayload, 4);
            $intake->update([
                'collected_category_id' => $catId,
                'current_step' => 'awaiting_description',
                'status' => 'incomplete',
            ]);

            $this->apiService->sendTextMessage(
                $conversation->fb_user_psid,
                'Please describe what happened and the current situation on scene (e.g., number of people affected, visible hazards).'
            );

            return;
        }

        if ($intake->current_step === 'awaiting_description' && trim($text) !== '') {
            $intake->update([
                'collected_description' => $text,
                'current_step' => 'awaiting_location',
                'status' => 'incomplete',
            ]);

            $this->apiService->sendTextMessage(
                $conversation->fb_user_psid,
                'Where is this incident occurring? Please send the specific street, landmark, or tap the Messenger location pin icon.'
            );

            return;
        }

        if ($intake->current_step === 'awaiting_location' && trim($text) !== '') {
            $intake->update([
                'location_details' => $text,
                'current_step' => 'awaiting_barangay',
                'status' => 'incomplete',
            ]);

            // Present top barangays as quick replies
            $barangays = Barangay::limit(6)->get();
            $replies = $barangays->map(fn ($b) => [
                'title' => Str::limit($b->name, 20),
                'payload' => 'BRGY_'.$b->id,
            ])->toArray();

            $this->apiService->sendQuickReply(
                $conversation->fb_user_psid,
                'Which Barangay in Hinoba-an is this located in?',
                $replies
            );

            return;
        }

        if (str_starts_with($quickReplyPayload ?? '', 'BRGY_') || $intake->current_step === 'awaiting_barangay') {
            $barangayId = null;
            if (str_starts_with($quickReplyPayload ?? '', 'BRGY_')) {
                $barangayId = (int) substr((string) $quickReplyPayload, 5);
            } else {
                $match = Barangay::where('name', 'like', "%{$text}%")->first();
                $barangayId = $match?->id;
            }

            if ($barangayId) {
                $intake->update([
                    'collected_barangay_id' => $barangayId,
                    'current_step' => 'awaiting_phone',
                    'status' => 'incomplete',
                ]);

                $this->apiService->sendTextMessage(
                    $conversation->fb_user_psid,
                    'Please provide your active Philippine mobile number (e.g. 09171234567) so our emergency dispatchers can contact and verify your report.'
                );

                return;
            }
        }

        // Contact Number Collection (validates 09XXXXXXXXX)
        if ($intake->current_step === 'awaiting_phone' || $this->containsPhoneNumber($text)) {
            $phone = $this->extractAndNormalizePhone($text);

            if ($phone !== null) {
                $intake->update([
                    'collected_phone' => $phone,
                    'current_step' => 'ready_for_review',
                    'status' => 'ready_for_review',
                    'is_complete' => true,
                ]);

                $conversation->update(['is_possible_incident' => true]);

                $this->apiService->sendTextMessage(
                    $conversation->fb_user_psid,
                    "✅ Salamat! Your emergency report has been logged and sent directly to Hinoba-an LDRRMO Command Center.\n\nAn officer is reviewing your information right now. Please keep your line (0{$phone}) open for callback."
                );

                IncidentIntakeUpdated::dispatch($intake);

                return;
            }

            if ($intake->current_step === 'awaiting_phone') {
                $this->apiService->sendTextMessage(
                    $conversation->fb_user_psid,
                    '⚠️ Invalid contact number. Please enter a valid 11-digit Philippine mobile number starting with 09 (e.g. 09171234567).'
                );

                return;
            }
        }

        // Default greeting / welcome menu if conversation has no active state
        if ($intake->current_step === 'awaiting_action' && ! $conversation->is_possible_incident) {
            $this->apiService->sendQuickReply(
                $conversation->fb_user_psid,
                'Hello! Welcome to the official Hinoba-an LDRRMO Emergency Messenger. How can we help you today?',
                [
                    ['title' => '🚨 Report Incident', 'payload' => 'REPORT_INCIDENT'],
                    ['title' => '📋 Instructions', 'payload' => 'REPORTING_INSTRUCTIONS'],
                    ['title' => '📞 Hotline Numbers', 'payload' => 'CONTACT_COMMAND_CENTER'],
                ]
            );
        }
    }

    /**
     * Process and securely download Facebook attachments to the private disk.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     */
    protected function processAttachments(FbMessage $message, array $attachments): void
    {
        $conversation = $message->conversation;

        foreach ($attachments as $att) {
            $type = $att['type'] ?? 'file';
            $url = $att['payload']['url'] ?? null;

            // Map Meta attachment types to internal enum
            $mappedType = match ($type) {
                'image' => 'image',
                'video' => 'video',
                'audio' => 'audio',
                'location' => 'location',
                default => 'file',
            };

            $savedMediaId = null;

            // Safely download image or video through centralized MediaManagementService
            if ($url && in_array($mappedType, ['image', 'video'], true)) {
                try {
                    $response = Http::timeout(15)->get($url);
                    if ($response->successful()) {
                        $extension = $mappedType === 'image' ? 'jpg' : 'mp4';
                        $filename = 'fb_'.uniqid().'.'.$extension;
                        $mimeType = $mappedType === 'image' ? 'image/jpeg' : 'video/mp4';

                        $media = app(MediaManagementService::class)->storeRemoteBinaryContent(
                            $response->body(),
                            $filename,
                            $mimeType,
                            [
                                'disk' => 'private',
                                'source_channel' => 'facebook_messenger',
                                'uploader_account_type' => 'facebook_citizen',
                                'attachable_type' => FbMessage::class,
                                'attachable_id' => $message->id,
                                'geo_context' => [
                                    'location_source' => MediaGeoMetadata::SOURCE_LOCATION_UNAVAILABLE,
                                ],
                            ]
                        );

                        $savedMediaId = $media->id;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed downloading FB attachment: '.$e->getMessage());
                }
            }

            FbAttachment::create([
                'fb_message_id' => $message->id,
                'attachment_type' => $mappedType,
                'url' => $url,
                'payload' => $att['payload'] ?? null,
                'saved_media_id' => $savedMediaId,
            ]);
        }
    }

    /**
     * Convert an Incident Intake Record into a formal ALERTO Incident.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function convertIntakeToIncident(IncidentIntakeRecord $intake, User $admin, array $overrides = []): Incident
    {
        if ($intake->status === 'converted' && $intake->incident) {
            return $intake->incident;
        }

        $year = date('Y');
        $random = strtoupper(Str::random(4));
        $count = Incident::whereYear('created_at', $year)->count() + 1;
        $reference = sprintf('INC-%s-%04d-%s-FB', $year, $count, $random);

        $categoryId = $overrides['category_id'] ?? ($intake->collected_category_id ?? IncidentCategory::first()?->id);
        $barangayId = $overrides['barangay_id'] ?? ($intake->collected_barangay_id ?? Barangay::first()?->id);
        $phone = $overrides['phone_number'] ?? ($intake->collected_phone ?? '09000000000');
        $description = $overrides['description'] ?? ($intake->collected_description ?? 'Emergency reported via Facebook Messenger.');

        $incident = Incident::create([
            'reference_number' => $reference,
            'source_channel' => 'facebook_messenger',
            'category_id' => $categoryId,
            'barangay_id' => $barangayId,
            'caller_name' => $intake->conversation->fullName(),
            'caller_phone' => $phone,
            'description' => $description,
            'priority' => $overrides['priority'] ?? 'high',
            'status' => 'pending_review', // STRICT INVARIANT: Always pending review, never auto-accepted!
            'submitted_at' => now(),
        ]);

        // Location Record
        $lat = $overrides['latitude'] ?? $intake->collected_latitude;
        $lng = $overrides['longitude'] ?? $intake->collected_longitude;

        if ($lat !== null && $lng !== null) {
            IncidentLocation::create([
                'incident_id' => $incident->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'landmark' => $intake->location_details ?? 'Reported via Messenger',
            ]);
        }

        // Contact verification record
        IncidentContactVerification::create([
            'incident_id' => $incident->id,
            'contact_number' => $phone,
            'verification_method' => 'callback',
            'status' => 'unverified',
        ]);

        // Link saved media attachments as incident evidence via centralized MediaManagementService
        $attachments = FbAttachment::whereHas('message', fn ($q) => $q->where('fb_conversation_id', $intake->fb_conversation_id))
            ->whereNotNull('saved_media_id')
            ->with('media')
            ->get();

        $mediaService = app(MediaManagementService::class);
        foreach ($attachments as $fbAtt) {
            if ($fbAtt->media) {
                $mediaService->linkMediaToIncident(
                    $fbAtt->media,
                    $incident,
                    'citizen_submission',
                    'Received from citizen via Facebook Messenger.',
                    true
                );
            }
        }

        $intake->update([
            'status' => 'converted',
            'generated_incident_id' => $incident->id,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        IncidentIntakeUpdated::dispatch($intake);

        return $incident;
    }

    /**
     * Send an Admin reply to a Facebook citizen.
     */
    public function sendAdminReply(FbConversation $conversation, string $text, User $admin): FbMessage
    {
        $result = $this->apiService->sendTextMessage(
            $conversation->fb_user_psid,
            $text,
            'CONFIRMED_EVENT_UPDATE'
        );

        $status = ($result['success'] ?? false) ? 'sent' : 'failed';
        $errorMessage = $result['error'] ?? null;

        $message = FbMessage::create([
            'fb_conversation_id' => $conversation->id,
            'fb_message_mid' => 'admin_reply_'.uniqid(),
            'direction' => 'outgoing',
            'message_text' => $text,
            'raw_payload' => array_merge($result, ['admin_id' => $admin->id]),
            'delivery_status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_interaction_at' => now(),
            'latest_message_text' => 'You: '.Str::limit($text, 250),
        ]);

        return $message;
    }

    /**
     * Get accessible conversations with optional search and status filters.
     *
     * @return Collection<int, FbConversation>
     */
    public function getConversations(?string $search = null, ?string $statusFilter = null): Collection
    {
        $query = FbConversation::query()
            ->with(['latestMessage', 'intakeRecord.category', 'intakeRecord.barangay', 'intakeRecord.incident'])
            ->orderByDesc('last_interaction_at');

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('fb_user_psid', 'like', "%{$term}%")
                    ->orWhere('latest_message_text', 'like', "%{$term}%")
                    ->orWhereHas('intakeRecord', fn ($i) => $i->where('collected_phone', 'like', "%{$term}%"));
            });
        }

        if ($statusFilter !== null && $statusFilter !== 'all') {
            if ($statusFilter === 'unread') {
                $query->where('unread_count', '>', 0);
            } elseif ($statusFilter === 'possible_incident') {
                $query->where('is_possible_incident', true);
            } elseif ($statusFilter === 'ready_for_review') {
                $query->whereHas('intakeRecord', fn ($i) => $i->where('status', 'ready_for_review'));
            } elseif ($statusFilter === 'incomplete') {
                $query->whereHas('intakeRecord', fn ($i) => $i->where('status', 'incomplete'));
            } elseif ($statusFilter === 'converted') {
                $query->whereHas('intakeRecord', fn ($i) => $i->where('status', 'converted'));
            } elseif ($statusFilter === 'rejected') {
                $query->whereHas('intakeRecord', fn ($i) => $i->where('status', 'rejected'));
            }
        }

        return $query->get();
    }

    /**
     * Validate Philippine mobile number format (09XXXXXXXXX or +639XXXXXXXXX).
     */
    public function isValidPhilippineMobile(string $phone): bool
    {
        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if (str_starts_with($clean, '639') && strlen($clean) === 12) {
            return true;
        }

        return str_starts_with($clean, '09') && strlen($clean) === 11;
    }

    /**
     * Normalize phone number to standard 09XXXXXXXXX.
     */
    public function extractAndNormalizePhone(string $text): ?string
    {
        // Match 09XXXXXXXXX or +639XXXXXXXXX anywhere in text
        if (preg_match('/(\+?639\d{9}|09\d{9})/', $text, $matches)) {
            $digits = preg_replace('/[^0-9]/', '', $matches[1]) ?? '';
            if (str_starts_with($digits, '639') && strlen($digits) === 12) {
                return '0'.substr($digits, 2);
            }
            if (str_starts_with($digits, '09') && strlen($digits) === 11) {
                return $digits;
            }
        }

        return null;
    }

    protected function containsPhoneNumber(string $text): bool
    {
        return (bool) preg_match('/(\+?639\d{9}|09\d{9})/', $text);
    }

    protected function detectEmergencyKeywords(string $text): bool
    {
        $keywords = [
            'fire', 'sunog', 'flood', 'baha', 'accident', 'banggaan', 'crash',
            'help', 'saklolo', 'tulong', 'emergency', 'rescue', 'evacuate',
            'landslide', 'drowning', 'unconscious', 'bleeding', 'hospital',
            'ambulan', 'patay', 'nasugatan', 'bagyo', 'typhoon', 'quake', 'lindol',
        ];

        $lower = strtolower($text);
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }
}
