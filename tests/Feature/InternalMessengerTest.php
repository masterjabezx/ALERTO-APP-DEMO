<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ConversationUpdated;
use App\Events\InternalMessageSent;
use App\Events\MessageRead;
use App\Models\Barangay;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\MediaAttachment;
use App\Models\Message;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InternalMessengerTest extends TestCase
{
    private function createAdmin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createOffice(string $prefix = 'OFF'): Office
    {
        $code = substr($prefix.'-'.uniqid(), 0, 32);

        return Office::create([
            'name' => 'Agency '.$code,
            'code' => $code,
            'department_type' => 'disaster',
            'contact_phone' => '0917'.rand(1000000, 9999999),
            'contact_email' => strtolower($code).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);
    }

    private function createOfficeUser(Office $office, string $level = 'office_admin'): User
    {
        $user = User::factory()->create([
            'account_type' => 'office',
            'is_active' => true,
        ]);

        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'office_level' => $level,
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        return $user;
    }

    private function createPersonnel(Office $office): User
    {
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
        ]);

        PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'BDG-'.uniqid(),
            'rank_designation' => 'Responder',
            'specialization' => 'Search & Rescue',
            'is_team_leader' => false,
        ]);

        return $user;
    }

    private function createCitizen(): User
    {
        return User::factory()->create([
            'account_type' => 'citizen',
            'is_active' => true,
        ]);
    }

    private function createIncident(): Incident
    {
        $category = IncidentCategory::first() ?? IncidentCategory::create([
            'name' => 'Flood',
            'slug' => 'flood-'.uniqid(),
            'icon' => 'waves',
            'color' => '#3187D8',
            'severity_level' => 'high',
        ]);

        $barangay = Barangay::first();
        $citizen = $this->createCitizen();

        $incident = Incident::create([
            'reference_number' => 'INC-'.uniqid(),
            'reporter_id' => $citizen->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'caller_name' => $citizen->name,
            'caller_phone' => '0917'.rand(1000000, 9999999),
            'status' => 'accepted',
            'priority' => 'high',
            'title' => 'Flash Flood Warning',
            'description' => 'Heavy rainfall causing water level rise in riverbank area.',
            'source_channel' => 'citizen_portal',
            'submitted_at' => now(),
        ]);

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 9.5855,
            'longitude' => 122.4820,
        ]);

        return $incident;
    }

    public function test_admin_can_access_internal_messenger(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.messages.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.messages.index');
    }

    public function test_personnel_can_access_internal_messenger(): void
    {
        $office = $this->createOffice('BFP');
        $personnel = $this->createPersonnel($office);

        $response = $this->actingAs($personnel)->get(route('personnel.messages'));

        $response->assertStatus(200);
        $response->assertViewIs('personnel.messages');
    }

    public function test_office_can_access_internal_messenger(): void
    {
        $office = $this->createOffice('PNP');
        $officeUser = $this->createOfficeUser($office);

        $response = $this->actingAs($officeUser)->get(route('office.messages'));

        $response->assertStatus(200);
        $response->assertViewIs('office.messages');
    }

    public function test_citizen_is_prohibited_from_internal_messenger(): void
    {
        $citizen = $this->createCitizen();

        // Web requests redirect citizen to their designated portal
        $this->actingAs($citizen)->get(route('admin.messages.index'))->assertRedirect(route('citizen.dashboard'));
        $this->actingAs($citizen)->get(route('office.messages'))->assertRedirect(route('citizen.dashboard'));
        $this->actingAs($citizen)->get(route('personnel.messages'))->assertRedirect(route('citizen.dashboard'));

        // API / JSON requests return 403 Forbidden
        $this->actingAs($citizen)->getJson(route('admin.messages.index'))->assertStatus(403);
        $this->actingAs($citizen)->getJson(route('office.messages'))->assertStatus(403);
        $this->actingAs($citizen)->getJson(route('personnel.messages'))->assertStatus(403);
    }

    public function test_admin_can_message_personnel_directly(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('RES');
        $personnel = $this->createPersonnel($office);

        // Start direct conversation
        $redirect = $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $redirect->assertRedirect();

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $admin->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $this->assertNotNull($conversation);

        // Admin sends message
        $response = $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), [
            'body' => 'Responder, please verify staging coordinates.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'body' => 'Responder, please verify staging coordinates.',
        ]);
    }

    public function test_personnel_can_message_admin_directly(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('MED');
        $personnel = $this->createPersonnel($office);

        // Start direct conversation
        $this->actingAs($personnel)->get(route('personnel.messages.direct', $admin->id))
            ->assertRedirect();

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $admin->id))
            ->first();

        $this->assertNotNull($conversation);

        // Personnel sends message
        $this->actingAs($personnel)->post(route('personnel.messages.send', $conversation->id), [
            'body' => 'Command, team has arrived at staging base.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $personnel->id,
            'body' => 'Command, team has arrived at staging base.',
        ]);
    }

    public function test_office_can_message_admin_directly(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('ENG');
        $officeUser = $this->createOfficeUser($office);

        // Start direct conversation
        $this->actingAs($officeUser)->get(route('office.messages.direct', $admin->id))
            ->assertRedirect();

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $officeUser->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $admin->id))
            ->first();

        $this->assertNotNull($conversation);

        $this->actingAs($officeUser)->post(route('office.messages.send', $conversation->id), [
            'body' => 'Heavy equipment unit deployed to Sitio Crossing.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $officeUser->id,
            'body' => 'Heavy equipment unit deployed to Sitio Crossing.',
        ]);
    }

    public function test_office_staff_can_message_same_office_colleague(): void
    {
        $office = $this->createOffice('COOP');
        $user1 = $this->createOfficeUser($office, 'office_admin');
        $user2 = $this->createOfficeUser($office, 'office_staff');

        $this->actingAs($user1)->get(route('office.messages.direct', $user2->id))
            ->assertRedirect();

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user1->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user2->id))
            ->first();

        $this->assertNotNull($conversation);
    }

    public function test_unauthorized_cross_office_direct_message_is_blocked(): void
    {
        $office1 = $this->createOffice('ONE');
        $office2 = $this->createOffice('TWO');

        $user1 = $this->createOfficeUser($office1);
        $user2 = $this->createOfficeUser($office2);

        $response = $this->actingAs($user1)->get(route('office.messages.direct', $user2->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_group_conversation(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('GRP');
        $personnel = $this->createPersonnel($office);
        $officeUser = $this->createOfficeUser($office);

        $title = 'Typhoon Rapid Response Joint Taskforce '.uniqid();

        $response = $this->actingAs($admin)->post(route('admin.messages.group'), [
            'title' => $title,
            'participant_ids' => [$personnel->id, $officeUser->id],
        ]);

        $response->assertRedirect();

        $conversation = Conversation::where('type', 'group')
            ->where('title', $title)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertTrue($conversation->participants()->where('user_id', $admin->id)->exists());
        $this->assertTrue($conversation->participants()->where('user_id', $personnel->id)->exists());
        $this->assertTrue($conversation->participants()->where('user_id', $officeUser->id)->exists());
    }

    public function test_non_admin_cannot_create_group_conversation(): void
    {
        $office = $this->createOffice('NON');
        $personnel = $this->createPersonnel($office);
        $colleague = $this->createPersonnel($office);

        $response = $this->actingAs($personnel)->postJson(route('admin.messages.group'), [
            'title' => 'Unauthorized Group Chat',
            'participant_ids' => [$colleague->id],
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_access_private_conversation(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('PRV');
        $personnelA = $this->createPersonnel($office);
        $personnelB = $this->createPersonnel($office);

        // Conversation between Admin and Personnel A
        $this->actingAs($admin)->get(route('admin.messages.direct', $personnelA->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnelA->id))
            ->first();

        // Personnel B (not participant, not admin) tries to view
        $this->actingAs($personnelB)->get(route('personnel.messages.show', $conversation->id))
            ->assertStatus(403);

        // Personnel B tries to send message
        $this->actingAs($personnelB)->post(route('personnel.messages.send', $conversation->id), [
            'body' => 'I should not be able to send this.',
        ])->assertStatus(403);
    }

    public function test_message_persistence_and_unread_counter(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('UNR');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        // Admin sends 3 messages
        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), ['body' => 'Message 1']);
        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), ['body' => 'Message 2']);
        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), ['body' => 'Message 3']);

        $this->assertEquals(3, $conversation->unreadCountFor($personnel->id));
        $this->assertEquals(0, $conversation->unreadCountFor($admin->id));
    }

    public function test_marking_conversation_as_read(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('READ');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), ['body' => 'Test unread']);
        $this->assertEquals(1, $conversation->unreadCountFor($personnel->id));

        // Personnel marks as read
        $this->actingAs($personnel)->post(route('personnel.messages.read', $conversation->id))
            ->assertJson(['status' => 'ok']);

        $this->assertEquals(0, $conversation->unreadCountFor($personnel->id));
    }

    public function test_sending_message_with_photo_attachment_stores_in_private_disk(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $office = $this->createOffice('PHOTO');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $photo = UploadedFile::fake()->create('incident_damage.jpg', 800, 'image/jpeg');

        $response = $this->actingAs($personnel)->post(route('personnel.messages.send', $conversation->id), [
            'body' => 'Visual evidence of roof collapse.',
            'attachment' => $photo,
        ]);

        $response->assertRedirect();

        $message = Message::where('conversation_id', $conversation->id)->latest('id')->first();
        $this->assertNotNull($message);
        $this->assertEquals('image', $message->message_type);
        $this->assertEquals(1, $message->attachments()->count());

        $media = $message->attachments->first();
        $this->assertEquals('private', $media->disk);
        $this->assertEquals('incident_damage.jpg', $media->file_name);
        Storage::disk('private')->assertExists($media->file_path);
    }

    public function test_sending_message_with_video_attachment(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $office = $this->createOffice('VID');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $video = UploadedFile::fake()->create('drone_recon.mp4', 2048, 'video/mp4');

        $this->actingAs($personnel)->post(route('personnel.messages.send', $conversation->id), [
            'body' => 'Drone reconnaissance feed.',
            'attachment' => $video,
        ])->assertRedirect();

        $message = Message::where('conversation_id', $conversation->id)->latest('id')->first();
        $this->assertEquals('video', $message->message_type);
    }

    public function test_sending_message_with_document_attachment(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $office = $this->createOffice('DOC');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $doc = UploadedFile::fake()->create('situation_report.pdf', 512, 'application/pdf');

        $this->actingAs($personnel)->post(route('personnel.messages.send', $conversation->id), [
            'body' => 'SitRep attached for review.',
            'attachment' => $doc,
        ])->assertRedirect();

        $message = Message::where('conversation_id', $conversation->id)->latest('id')->first();
        $this->assertEquals('document', $message->message_type);
    }

    public function test_geo_tag_metadata_persisted_with_device_live_upload(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $office = $this->createOffice('GEO');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $photo = UploadedFile::fake()->create('road_block.jpg', 500, 'image/jpeg');

        $this->actingAs($personnel)->post(route('personnel.messages.send', $conversation->id), [
            'body' => 'Road blocked by fallen tree.',
            'attachment' => $photo,
            'latitude' => 9.584120,
            'longitude' => 122.483150,
            'accuracy' => 5.2,
            'gps_source' => 'device_live_upload',
        ])->assertRedirect();

        $message = Message::where('conversation_id', $conversation->id)->latest('id')->first();
        $media = $message->attachments->first();

        $this->assertNotNull($media->geoMetadata);
        $this->assertEquals('device_live_upload', $media->geoMetadata->gps_source);
        $this->assertEquals('9.58412000', (string) $media->geoMetadata->latitude);
        $this->assertEquals('122.48315000', (string) $media->geoMetadata->longitude);
        $this->assertFalse($media->geoMetadata->is_inherited);
    }

    public function test_geo_tag_metadata_persisted_with_incident_inherited(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $incident = $this->createIncident();

        $this->actingAs($admin)->get(route('admin.messages.incident', $incident->reference_number));
        $conversation = Conversation::where('type', 'incident')
            ->where('incident_id', $incident->id)
            ->first();

        $photo = UploadedFile::fake()->create('field_survey.jpg', 500, 'image/jpeg');

        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), [
            'body' => 'Damage assessment photo inheriting incident location.',
            'attachment' => $photo,
            'inherit_incident_location' => 1,
        ])->assertRedirect();

        $message = Message::where('conversation_id', $conversation->id)->latest('id')->first();
        $media = $message->attachments->first();

        $this->assertNotNull($media->geoMetadata);
        $this->assertEquals('incident_inherited', $media->geoMetadata->gps_source);
        $this->assertTrue($media->geoMetadata->is_inherited);
        $this->assertEquals('9.58550000', (string) $media->geoMetadata->latitude);
        $this->assertEquals('122.48200000', (string) $media->geoMetadata->longitude);
    }

    public function test_incident_linked_conversation_creation_and_routing(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('INC');
        $personnel = $this->createPersonnel($office);
        $incident = $this->createIncident();

        // Personnel taps "MESSAGE ADMIN ABOUT THIS INCIDENT"
        $response = $this->actingAs($personnel)->get(route('personnel.messages.incident', $incident->reference_number));
        $response->assertRedirect();

        $conversation = Conversation::where('type', 'incident')
            ->where('incident_id', $incident->id)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertTrue($conversation->participants()->where('user_id', $personnel->id)->exists());
        $this->assertTrue($conversation->participants()->where('user_id', $admin->id)->exists());
        $this->assertStringContainsString($incident->reference_number, $conversation->title);
    }

    public function test_authorized_user_can_download_message_attachment(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $office = $this->createOffice('DL');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $file = UploadedFile::fake()->create('evacuation_map.pdf', 300, 'application/pdf');

        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), [
            'body' => 'Evacuation staging diagram.',
            'attachment' => $file,
        ]);

        $media = MediaAttachment::latest('id')->first();

        // Both Admin and Personnel can download
        $this->actingAs($personnel)->get(route('messenger.attachments.download', $media->id))
            ->assertStatus(200);

        $this->actingAs($admin)->get(route('messenger.attachments.download', $media->id))
            ->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_download_message_attachment(): void
    {
        Storage::fake('private');

        $admin = $this->createAdmin();
        $office = $this->createOffice('NODL');
        $personnelA = $this->createPersonnel($office);
        $personnelB = $this->createPersonnel($office);
        $citizen = $this->createCitizen();

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnelA->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnelA->id))
            ->first();

        $file = UploadedFile::fake()->create('confidential_dispatch.pdf', 300, 'application/pdf');

        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), [
            'body' => 'Confidential operational plan.',
            'attachment' => $file,
        ]);

        $media = MediaAttachment::latest('id')->first();

        // Personnel B is not a participant
        $this->actingAs($personnelB)->get(route('messenger.attachments.download', $media->id))
            ->assertStatus(403);

        // Citizen is barred
        $this->actingAs($citizen)->get(route('messenger.attachments.download', $media->id))
            ->assertStatus(403);
    }

    public function test_real_time_events_dispatched_on_message_send_and_read(): void
    {
        Event::fake([
            InternalMessageSent::class,
            ConversationUpdated::class,
            MessageRead::class,
        ]);

        $admin = $this->createAdmin();
        $office = $this->createOffice('EVT');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $this->actingAs($admin)->post(route('admin.messages.send', $conversation->id), [
            'body' => 'Event test dispatch message.',
        ]);

        Event::assertDispatched(InternalMessageSent::class);
        Event::assertDispatched(ConversationUpdated::class);

        // Mark as read
        $this->actingAs($personnel)->post(route('personnel.messages.read', $conversation->id));

        Event::assertDispatched(MessageRead::class);
    }

    public function test_conversation_archiving(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('ARC');
        $personnel = $this->createPersonnel($office);

        $this->actingAs($admin)->get(route('admin.messages.direct', $personnel->id));
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $personnel->id))
            ->first();

        $this->assertFalse($conversation->fresh()->is_archived);

        $this->actingAs($admin)->post(route('admin.messages.archive', $conversation->id), [
            'archive' => 1,
        ])->assertRedirect();

        $this->assertTrue($conversation->fresh()->is_archived);
    }
}
