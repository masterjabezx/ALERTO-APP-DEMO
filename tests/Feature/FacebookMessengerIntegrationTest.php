<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\FbMessageReceived;
use App\Events\IncidentIntakeUpdated;
use App\Models\Barangay;
use App\Models\FbAttachment;
use App\Models\FbConversation;
use App\Models\FbIntegrationSetting;
use App\Models\FbMessage;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentEvidenceRecord;
use App\Models\IncidentIntakeRecord;
use App\Models\MediaAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacebookMessengerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.verify_token' => 'test_alerto_verify_token',
            'services.meta.app_secret' => 'test_meta_app_secret_12345',
            'services.meta.webhook_secret' => 'test_meta_app_secret_12345',
            'services.meta.page_access_token' => 'test_page_access_token',
        ]);

        FbIntegrationSetting::firstOrCreate(
            ['page_id' => '100234567890'],
            [
                'page_name' => 'Hinoba-an LDRRMO Official',
                'app_id' => '5432167890',
                'verify_token' => 'test_alerto_verify_token',
                'is_active' => true,
                'webhook_verified' => true,
            ]
        );
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCitizen(): User
    {
        return User::factory()->create([
            'account_type' => 'citizen',
            'is_active' => true,
        ]);
    }

    private function createCategory(): IncidentCategory
    {
        return IncidentCategory::first() ?? IncidentCategory::create([
            'name' => 'Typhoon',
            'slug' => 'typhoon-'.uniqid(),
            'icon' => 'wind',
            'color' => '#3187D8',
            'severity_level' => 'high',
        ]);
    }

    private function createBarangay(): Barangay
    {
        return Barangay::first();
    }

    public function test_webhook_verification_with_valid_token(): void
    {
        $response = $this->get('/webhooks/facebook/messenger?hub_mode=subscribe&hub_verify_token=test_alerto_verify_token&hub_challenge=1158201444');

        $response->assertStatus(200);
        $this->assertEquals('1158201444', $response->getContent());
    }

    public function test_webhook_verification_with_invalid_token(): void
    {
        $response = $this->get('/webhooks/facebook/messenger?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=1158201444');

        $response->assertStatus(403);
    }

    public function test_valid_incoming_webhook_message(): void
    {
        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'time' => 1700000000,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'PSID_'.uniqid()],
                            'recipient' => ['id' => '100234567890'],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => 'mid_'.uniqid(),
                                'text' => 'Hello Hinoba-an LDRRMO!',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawBody = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $rawBody, 'test_meta_app_secret_12345');

        $response = $this->call(
            'POST',
            '/webhooks/facebook/messenger',
            [],
            [],
            [],
            ['HTTP_X-Hub-Signature-256' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'EVENT_RECEIVED']);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $payload = ['object' => 'page', 'entry' => []];
        $rawBody = json_encode($payload);

        $response = $this->call(
            'POST',
            '/webhooks/facebook/messenger',
            [],
            [],
            [],
            ['HTTP_X-Hub-Signature-256' => 'sha256=invalid_hash', 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(403);
    }

    public function test_duplicate_webhook_event_is_deduplicated(): void
    {
        $mid = 'mid_dedup_'.uniqid();
        $psid = 'PSID_DEDUP_'.uniqid();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'time' => 1700000000,
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'recipient' => ['id' => '100234567890'],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => $mid,
                                'text' => 'Duplicate event test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawBody = json_encode($payload);

        // Send first time
        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], $rawBody)
            ->assertStatus(200);

        // Send second time (duplicate)
        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], $rawBody)
            ->assertStatus(200);

        // Assert only 1 message record created in database
        $count = FbMessage::where('fb_message_mid', $mid)->count();
        $this->assertEquals(1, $count);
    }

    public function test_incoming_messenger_text_persists_and_updates_conversation(): void
    {
        $psid = 'PSID_TEXT_'.uniqid();
        $mid = 'mid_text_'.uniqid();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => $mid,
                                'text' => 'Emergency report: road block at Barangay Bacuyangan.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $conversation = FbConversation::where('fb_user_psid', $psid)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals(1, $conversation->unread_count);

        $message = FbMessage::where('fb_message_mid', $mid)->first();
        $this->assertNotNull($message);
        $this->assertEquals('incoming', $message->direction);
        $this->assertEquals('Emergency report: road block at Barangay Bacuyangan.', $message->message_text);
    }

    public function test_incoming_photo_attachment_downloads_to_private_disk(): void
    {
        Storage::fake('private');
        Http::fake([
            'https://cdn.facebook.com/fake_photo.jpg' => Http::response('fake_image_binary_content', 200),
        ]);

        $psid = 'PSID_PHOTO_'.uniqid();
        $mid = 'mid_photo_'.uniqid();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => $mid,
                                'text' => 'Here is the photo of the flood.',
                                'attachments' => [
                                    [
                                        'type' => 'image',
                                        'payload' => ['url' => 'https://cdn.facebook.com/fake_photo.jpg'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $message = FbMessage::where('fb_message_mid', $mid)->first();
        $this->assertNotNull($message);

        $attachment = FbAttachment::where('fb_message_id', $message->id)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('image', $attachment->attachment_type);
        $this->assertNotNull($attachment->saved_media_id);

        $media = $attachment->media;
        $this->assertEquals('private', $media->disk);
        Storage::disk('private')->assertExists($media->file_path);
    }

    public function test_incoming_video_attachment(): void
    {
        Storage::fake('private');
        Http::fake([
            'https://cdn.facebook.com/fake_video.mp4' => Http::response('fake_video_binary_content', 200),
        ]);

        $psid = 'PSID_VID_'.uniqid();
        $mid = 'mid_vid_'.uniqid();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => $mid,
                                'text' => 'Video evidence',
                                'attachments' => [
                                    [
                                        'type' => 'video',
                                        'payload' => ['url' => 'https://cdn.facebook.com/fake_video.mp4'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $attachment = FbAttachment::whereHas('message', fn ($q) => $q->where('fb_message_mid', $mid))->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('video', $attachment->attachment_type);
    }

    public function test_explicit_messenger_location_attachment_extracts_coordinates(): void
    {
        $psid = 'PSID_LOC_'.uniqid();
        $mid = 'mid_loc_'.uniqid();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => $mid,
                                'attachments' => [
                                    [
                                        'type' => 'location',
                                        'payload' => [
                                            'coordinates' => [
                                                'lat' => 9.584120,
                                                'long' => 122.483150,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $conversation = FbConversation::where('fb_user_psid', $psid)->first();
        $intake = $conversation->intakeRecord;

        $this->assertNotNull($intake);
        $this->assertEquals('9.58412000', (string) $intake->collected_latitude);
        $this->assertEquals('122.48315000', (string) $intake->collected_longitude);
        $this->assertEquals('messenger_gps', $intake->location_source);
    }

    public function test_contact_number_collection_with_valid_philippine_mobile(): void
    {
        $psid = 'PSID_PHONE_'.uniqid();
        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => $psid,
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'current_step' => 'awaiting_phone',
            'status' => 'incomplete',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => 'mid_'.uniqid(),
                                'text' => 'My number is 09173456789, please call me immediately.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $intake->refresh();
        $this->assertEquals('09173456789', $intake->collected_phone);
        $this->assertEquals('ready_for_review', $intake->status);
        $this->assertTrue($intake->is_complete);
    }

    public function test_invalid_phone_number_prompts_reentry(): void
    {
        $psid = 'PSID_INVALID_PHONE_'.uniqid();
        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => $psid,
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'current_step' => 'awaiting_phone',
            'status' => 'incomplete',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => 'mid_'.uniqid(),
                                'text' => 'Call me at 12345',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $intake->refresh();
        $this->assertNull($intake->collected_phone);
        $this->assertEquals('awaiting_phone', $intake->current_step);
    }

    public function test_incomplete_incident_intake_saved(): void
    {
        $psid = 'PSID_INCOMPLETE_'.uniqid();
        $category = $this->createCategory();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => $psid,
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_description' => 'Bridge flooded, water level 3 feet.',
            'current_step' => 'awaiting_phone',
            'status' => 'incomplete',
            'is_complete' => false,
        ]);

        $this->assertFalse($intake->is_complete);
        $this->assertEquals('incomplete', $intake->status);
    }

    public function test_ready_for_review_intake_workflow(): void
    {
        $psid = 'PSID_READY_'.uniqid();
        $category = $this->createCategory();
        $barangay = $this->createBarangay();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => $psid,
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_barangay_id' => $barangay ? $barangay->id : 1,
            'collected_description' => 'House inundated by flood waters.',
            'collected_phone' => '09171234567',
            'collected_latitude' => 9.5855,
            'collected_longitude' => 122.4820,
            'current_step' => 'ready_for_review',
            'status' => 'ready_for_review',
            'is_complete' => true,
        ]);

        $this->assertTrue($intake->is_complete);
        $this->assertEquals('ready_for_review', $intake->status);
    }

    public function test_incident_intake_conversion_creates_pending_review_incident(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $barangay = $this->createBarangay();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_CONV_'.uniqid(),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_barangay_id' => $barangay ? $barangay->id : 1,
            'collected_description' => 'Severe flash flooding near central plaza.',
            'collected_phone' => '09187654321',
            'collected_latitude' => 9.5855,
            'collected_longitude' => 122.4820,
            'current_step' => 'ready_for_review',
            'status' => 'ready_for_review',
            'is_complete' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.communications.fb.intakes.convert', $intake->id), [
            'category_id' => $category->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'phone_number' => '09187654321',
            'description' => 'Severe flash flooding near central plaza.',
            'priority' => 'critical',
            'latitude' => 9.5855,
            'longitude' => 122.4820,
        ]);

        $response->assertRedirect();

        $intake->refresh();
        $this->assertEquals('converted', $intake->status);
        $this->assertNotNull($intake->generated_incident_id);

        $incident = $intake->incident;
        $this->assertNotNull($incident);
        $this->assertEquals('facebook_messenger', $incident->source_channel);
        $this->assertEquals('pending_review', $incident->status);
        $this->assertEquals('critical', $incident->priority);
    }

    public function test_unique_reference_generation_for_converted_incident(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $barangay = $this->createBarangay();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_REF_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_barangay_id' => $barangay ? $barangay->id : 1,
            'collected_phone' => '09170001122',
            'collected_description' => 'Fallen tree on power lines.',
            'status' => 'ready_for_review',
        ]);

        $this->actingAs($admin)->post(route('admin.communications.fb.intakes.convert', $intake->id), [
            'category_id' => $category->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'phone_number' => '09170001122',
            'description' => 'Fallen tree on power lines.',
            'priority' => 'high',
        ]);

        $intake->refresh();
        $this->assertStringEndsWith('-FB', $intake->incident->reference_number);
    }

    public function test_messenger_source_attribution_preserved(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $barangay = $this->createBarangay();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_SRC_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_barangay_id' => $barangay ? $barangay->id : 1,
            'collected_phone' => '09179998877',
            'collected_description' => 'Source attribution test',
            'status' => 'ready_for_review',
        ]);

        $this->actingAs($admin)->post(route('admin.communications.fb.intakes.convert', $intake->id), [
            'category_id' => $category->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'phone_number' => '09179998877',
            'description' => 'Source attribution test',
            'priority' => 'medium',
        ]);

        $intake->refresh();
        $this->assertEquals('facebook_messenger', $intake->incident->source_channel);
    }

    public function test_original_conversation_and_evidence_linked(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $barangay = $this->createBarangay();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_EVID_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $msg = FbMessage::create([
            'fb_conversation_id' => $conversation->id,
            'fb_message_mid' => 'mid_evid_'.uniqid(),
            'direction' => 'incoming',
            'message_text' => 'Here is damage photo',
            'raw_payload' => [],
            'delivery_status' => 'delivered',
            'sent_at' => now(),
        ]);

        $media = MediaAttachment::create([
            'uploader_id' => null,
            'disk' => 'private',
            'file_path' => 'facebook_attachments/test.jpg',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'test'),
            'created_at' => now(),
        ]);

        FbAttachment::create([
            'fb_message_id' => $msg->id,
            'attachment_type' => 'image',
            'saved_media_id' => $media->id,
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_barangay_id' => $barangay ? $barangay->id : 1,
            'collected_phone' => '09171234567',
            'collected_description' => 'Evidence linking test',
            'status' => 'ready_for_review',
        ]);

        $this->actingAs($admin)->post(route('admin.communications.fb.intakes.convert', $intake->id), [
            'category_id' => $category->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'phone_number' => '09171234567',
            'description' => 'Evidence linking test',
            'priority' => 'high',
        ]);

        $intake->refresh();
        $incident = $intake->incident;

        $evidenceRecord = IncidentEvidenceRecord::where('incident_id', $incident->id)
            ->where('media_id', $media->id)
            ->first();

        $this->assertNotNull($evidenceRecord);
    }

    public function test_admin_inbox_permissions(): void
    {
        $admin = $this->createAdmin();
        $citizen = $this->createCitizen();

        // Admin has access
        $this->actingAs($admin)->get(route('admin.communications.fb.inbox'))
            ->assertStatus(200);

        // Citizen is barred
        $this->actingAs($citizen)->get(route('admin.communications.fb.inbox'))
            ->assertRedirect(route('citizen.dashboard'));

        $this->actingAs($citizen)->getJson(route('admin.communications.fb.inbox'))
            ->assertStatus(403);
    }

    public function test_admin_reply_dispatches_outbound_message(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'message_id' => 'm_test_reply_123',
                'recipient_id' => '123456',
            ], 200),
        ]);

        $admin = $this->createAdmin();
        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_REPLY_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.communications.fb.reply', $conversation->id), [
            'message_text' => 'Rescue boat Alpha-1 is dispatched to your sector.',
        ]);

        $response->assertRedirect();

        $message = FbMessage::where('fb_conversation_id', $conversation->id)
            ->where('direction', 'outgoing')
            ->first();

        $this->assertNotNull($message);
        $this->assertEquals('Rescue boat Alpha-1 is dispatched to your sector.', $message->message_text);
        $this->assertEquals('sent', $message->delivery_status);
    }

    public function test_meta_api_error_handling_marks_delivery_failed(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => '(#10) This message is outside the allowed 24-hour window.',
                    'type' => 'OAuthException',
                    'code' => 10,
                    'error_subcode' => 2018001,
                ],
            ], 400),
        ]);

        $admin = $this->createAdmin();
        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_ERR_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.communications.fb.reply', $conversation->id), [
            'message_text' => 'Follow up message after 24h window.',
        ]);

        $message = FbMessage::where('fb_conversation_id', $conversation->id)
            ->where('direction', 'outgoing')
            ->latest('id')
            ->first();

        $this->assertNotNull($message);
        $this->assertEquals('failed', $message->delivery_status);
        $this->assertNotNull($message->error_message);
    }

    public function test_outbound_message_delivery_status_persisted(): void
    {
        $admin = $this->createAdmin();
        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_STATUS_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.communications.fb.reply', $conversation->id), [
            'message_text' => 'Status check message',
        ]);

        $message = FbMessage::where('fb_conversation_id', $conversation->id)
            ->where('direction', 'outgoing')
            ->first();

        $this->assertNotNull($message->delivery_status);
    }

    public function test_reverb_notification_broadcasting(): void
    {
        Event::fake([
            FbMessageReceived::class,
            IncidentIntakeUpdated::class,
        ]);

        $psid = 'PSID_EVT_'.uniqid();
        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100234567890',
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'timestamp' => 1700000000,
                            'message' => [
                                'mid' => 'mid_'.uniqid(),
                                'text' => 'Fire emergency report',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->call('POST', '/webhooks/facebook/messenger', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        Event::assertDispatched(FbMessageReceived::class);
    }

    public function test_unauthorized_user_cannot_convert_intake(): void
    {
        $citizen = $this->createCitizen();
        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_UNAUTH_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'status' => 'ready_for_review',
        ]);

        $response = $this->actingAs($citizen)->postJson(route('admin.communications.fb.intakes.convert', $intake->id), [
            'category_id' => 1,
            'barangay_id' => 1,
            'phone_number' => '09170001122',
            'description' => 'Unauthorized convert attempt',
            'priority' => 'high',
        ]);

        $response->assertStatus(403);
    }

    public function test_dispatch_protection_on_messenger_incident(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $barangay = $this->createBarangay();

        $conversation = FbConversation::create([
            'page_id' => '100234567890',
            'fb_user_psid' => 'PSID_PROT_'.uniqid(),
            'last_interaction_at' => now(),
        ]);

        $intake = IncidentIntakeRecord::create([
            'fb_conversation_id' => $conversation->id,
            'collected_category_id' => $category->id,
            'collected_barangay_id' => $barangay ? $barangay->id : 1,
            'collected_phone' => '09170001122',
            'collected_description' => 'Dispatch protection verification',
            'status' => 'ready_for_review',
        ]);

        $this->actingAs($admin)->post(route('admin.communications.fb.intakes.convert', $intake->id), [
            'category_id' => $category->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'phone_number' => '09170001122',
            'description' => 'Dispatch protection verification',
            'priority' => 'high',
        ]);

        $intake->refresh();
        $incident = $intake->incident;

        // Incident MUST be pending_review
        $this->assertEquals('pending_review', $incident->status);

        // Attempting to dispatch an incident while in pending_review should be blocked by AdminIncidentService
        $dispatchResponse = $this->actingAs($admin)->post(route('admin.incidents.dispatch', $incident->reference_number), [
            'dispatch_notes' => 'Premature dispatch attempt',
        ]);

        // Status must remain pending_review, NOT dispatched
        $incident->refresh();
        $this->assertEquals('pending_review', $incident->status);
    }
}
