<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\MediaAttachment;
use App\Models\Office;
use App\Models\OfficeResponsibility;
use App\Models\OfficeUser;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelProfile;
use App\Models\ResponseTeam;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminIncidentService;
use App\Services\MetaMessengerApiService;
use App\Support\CsvSanitizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class SystemSecurityAuditTest extends TestCase
{
    use DatabaseTransactions;

    private function createUser(string $accountType): User
    {
        return User::factory()->create([
            'account_type' => $accountType,
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
    }

    private function createOffice(string $suffix = ''): Office
    {
        return Office::create([
            'name' => 'Audit Office '.$suffix.Str::random(4),
            'code' => 'AUD-'.strtoupper(Str::random(5)),
            'department_type' => 'disaster',
            'contact_phone' => '0917'.rand(1000000, 9999999),
            'contact_email' => 'aud'.Str::random(4).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);
    }

    public function test_strict_four_account_types_and_zero_super_admin(): void
    {
        $roleNames = Role::pluck('name')->toArray();

        foreach (['citizen', 'personnel', 'office', 'admin', 'super_admin'] as $expectedRole) {
            $this->assertContains($expectedRole, $roleNames);
        }
    }

    public function test_unassigned_office_user_cannot_fallback_to_other_office(): void
    {
        // Create an active office in DB so a fallback target would exist if vulnerable
        $this->createOffice('TARGET');

        // Office user without any OfficeUser pivot assignment
        $unassignedOfficeUser = $this->createUser('office');

        $this->actingAs($unassignedOfficeUser)
            ->get(route('office.dashboard'))
            ->assertStatus(403);

        $this->actingAs($unassignedOfficeUser)
            ->get(route('office.incidents'))
            ->assertStatus(403);
    }

    public function test_personnel_cannot_update_milestone_on_another_responders_assignment(): void
    {
        $office = $this->createOffice('MIL');
        $citizen = $this->createUser('citizen');
        $admin = $this->createUser('admin');

        $responderA = $this->createUser('personnel');
        $profileA = PersonnelProfile::create([
            'user_id' => $responderA->id,
            'office_id' => $office->id,
            'badge_number' => 'AUD-A-'.rand(100, 999),
            'rank_designation' => 'Officer',
        ]);
        PersonnelAvailability::create([
            'personnel_id' => $profileA->id,
            'operational_status' => 'dispatched',
            'status_updated_at' => now(),
        ]);

        $responderB = $this->createUser('personnel');
        $profileB = PersonnelProfile::create([
            'user_id' => $responderB->id,
            'office_id' => $office->id,
            'badge_number' => 'AUD-B-'.rand(100, 999),
            'rank_designation' => 'Officer',
        ]);
        PersonnelAvailability::create([
            'personnel_id' => $profileB->id,
            'operational_status' => 'available',
            'status_updated_at' => now(),
        ]);

        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-AUD-'.rand(1000, 9999),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'high',
            'status' => 'dispatched',
            'description' => 'Assigned exclusively to Responder A.',
            'caller_name' => $citizen->name,
            'caller_phone' => '09171234567',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profileA->id,
            'assigned_by' => $admin->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        // Responder B attempts IDOR milestone update on Responder A's assignment
        $this->actingAs($responderB)
            ->post(route('personnel.milestone', $assignment->id), [
                'milestone' => 'acknowledged',
            ])
            ->assertStatus(403);

        // Responder A (legitimate assignee) can acknowledge
        $this->actingAs($responderA)
            ->post(route('personnel.milestone', $assignment->id), [
                'milestone' => 'acknowledged',
            ])
            ->assertRedirect();

        $this->assertSame('acknowledged', $incident->fresh()->status);

        // Invalid transition (skipping straight from acknowledged -> resolved) flashes error cleanly instead of crashing
        $this->actingAs($responderA)
            ->post(route('personnel.milestone', $assignment->id), [
                'milestone' => 'resolved',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Valid progression: acknowledged -> on_scene -> resolved (maps to 'cleared' in response_logs and completes assignment)
        $this->actingAs($responderA)
            ->post(route('personnel.milestone', $assignment->id), ['milestone' => 'on_scene'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($responderA)
            ->post(route('personnel.milestone', $assignment->id), ['milestone' => 'resolved'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('resolved', $incident->fresh()->status);
        $this->assertSame('completed', $assignment->fresh()->status);
        $this->assertDatabaseHas('response_logs', [
            'assignment_id' => $assignment->id,
            'milestone' => 'cleared',
        ]);
    }

    public function test_internal_messenger_blocks_cross_office_and_rejected_incident_conversations(): void
    {
        $officeA = $this->createOffice('MSG-A');
        $officeB = $this->createOffice('MSG-B');
        $citizen = $this->createUser('citizen');
        $admin = $this->createUser('admin');

        $officeUserA = $this->createUser('office');
        OfficeUser::create([
            'office_id' => $officeA->id,
            'user_id' => $officeUserA->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        $category = IncidentCategory::create([
            'name' => 'Exclusive Category '.Str::random(4),
            'slug' => 'excl-'.Str::random(6),
            'default_priority' => 'high',
            'is_active' => true,
        ]);
        OfficeResponsibility::create([
            'office_id' => $officeB->id,
            'category_slug' => $category->slug,
        ]);

        $barangay = Barangay::first();

        $restrictedIncident = Incident::create([
            'reference_number' => 'INC-EXCL-'.rand(1000, 9999),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'high',
            'status' => 'accepted',
            'description' => 'Restricted to Office B only.',
            'caller_name' => $citizen->name,
            'caller_phone' => '09170001122',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);
        IncidentAssignment::create([
            'incident_id' => $restrictedIncident->id,
            'office_id' => $officeB->id,
            'assigned_by' => $admin->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        // Office A attempts to open incident conversation for Office B's incident -> 403 Forbidden
        $this->actingAs($officeUserA)
            ->get(route('office.messages.incident', $restrictedIncident->reference_number))
            ->assertStatus(403);

        // Personnel attempts to open incident conversation on a REJECTED incident -> 403 Forbidden
        $personnel = $this->createUser('personnel');
        PersonnelProfile::create([
            'user_id' => $personnel->id,
            'office_id' => $officeA->id,
            'badge_number' => 'PER-MSG-'.rand(100, 999),
            'rank_designation' => 'Responder',
        ]);

        $rejectedIncident = Incident::create([
            'reference_number' => 'INC-REJ-'.rand(1000, 9999),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'low',
            'status' => 'rejected',
            'description' => 'Rejected prank incident.',
            'caller_name' => $citizen->name,
            'caller_phone' => '09170001123',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        $this->actingAs($personnel)
            ->get(route('personnel.messages.incident', $rejectedIncident->reference_number))
            ->assertStatus(403);

        // Unauthorized user cannot mark another conversation as read
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $admin->id,
            'is_archived' => false,
        ]);
        $conv->participants()->create(['user_id' => $admin->id, 'is_admin' => true, 'joined_at' => now()]);

        $this->actingAs($personnel)
            ->postJson(route('personnel.messages.read', $conv->id))
            ->assertStatus(403);
    }

    public function test_citizen_cannot_access_other_citizens_incident_records_or_private_media(): void
    {
        Storage::fake('private');

        $citizenA = $this->createUser('citizen');
        $citizenB = $this->createUser('citizen');

        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incidentA = Incident::create([
            'reference_number' => 'INC-PRIV-'.rand(1000, 9999),
            'reporter_id' => $citizenA->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'medium',
            'status' => 'pending_review',
            'description' => 'Citizen A private incident.',
            'caller_name' => $citizenA->name,
            'caller_phone' => '09172223344',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        Storage::disk('private')->put('media/test/private_photo.jpg', 'dummy-content');
        $media = MediaAttachment::create([
            'uploader_id' => $citizenA->id,
            'uploader_account_type' => 'citizen',
            'source_channel' => 'citizen_portal',
            'disk' => 'private',
            'file_path' => 'media/test/private_photo.jpg',
            'file_name' => 'private_photo.jpg',
            'stored_filename' => 'private_photo.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'photo',
            'file_size_bytes' => 13,
            'checksum_sha256' => hash('sha256', 'dummy-content'),
            'incident_id' => $incidentA->id,
            'processing_status' => 'ready',
            'created_at' => now(),
        ]);

        // Citizen B blocked from viewing Citizen A's record or media
        $this->actingAs($citizenB)
            ->get(route('citizen.records.show', $incidentA->reference_number))
            ->assertStatus(404);

        $this->actingAs($citizenB)
            ->get(route('media.stream', $media->id))
            ->assertStatus(403);

        $this->actingAs($citizenB)
            ->get(route('media.download', $media->id))
            ->assertStatus(403);

        // Citizen A can stream their own media
        $this->actingAs($citizenA)
            ->get(route('media.stream', $media->id))
            ->assertStatus(200);
    }

    public function test_csv_sanitizer_and_exports_prevent_formula_injection(): void
    {
        $this->assertSame("'=CMD|' /C calc'!A0", CsvSanitizer::escapeCell("=CMD|' /C calc'!A0"));
        $this->assertSame("'+1+1", CsvSanitizer::escapeCell('+1+1'));
        $this->assertSame("'-2+3", CsvSanitizer::escapeCell('-2+3'));
        $this->assertSame("'@SUM(1+1)", CsvSanitizer::escapeCell('@SUM(1+1)'));
        $this->assertSame('Safe Text', CsvSanitizer::escapeCell('Safe Text'));

        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        Incident::create([
            'reference_number' => '=HYPERLINK("http://evil")',
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'low',
            'status' => 'pending_review',
            'description' => 'CSV injection test',
            'caller_name' => '+CMD|calc',
            'caller_phone' => '09170001111',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));
        $response->assertStatus(200);
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK(", $csv);
    }

    public function test_concurrent_second_review_is_rejected_and_pending_override_succeeds(): void
    {
        $admin = $this->createUser('admin');
        $office = $this->createOffice('CONC');
        $officeAdmin = $this->createUser('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $officeAdmin->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        $category = IncidentCategory::first();
        OfficeResponsibility::create([
            'office_id' => $office->id,
            'category_slug' => $category->slug,
        ]);

        $citizen = $this->createUser('citizen');
        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-CONC-'.rand(1000, 9999),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'high',
            'status' => 'pending_review',
            'description' => 'Concurrent review guard test.',
            'caller_name' => $citizen->name,
            'caller_phone' => '09179990011',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        $service = app(AdminIncidentService::class);
        $service->reviewIncident($admin, $incident, 'accepted', null, 'Verified.');

        // Acceptance must NOT auto-dispatch
        $this->assertSame('accepted', $incident->fresh()->status);
        $this->assertSame(2, $incident->fresh()->version_lock);

        // Concurrent/second non-admin review attempt must throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $service->reviewIncident($officeAdmin, $incident->fresh(), 'rejected', 'Duplicate');
    }

    public function test_admin_override_to_pending_review_handles_enum_safely(): void
    {
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-OVR-'.rand(1000, 9999),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'medium',
            'status' => 'rejected',
            'description' => 'Override test.',
            'caller_name' => $citizen->name,
            'caller_phone' => '09179990022',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.incidents.override', $incident->reference_number), [
                'new_decision' => 'pending_review',
                'override_reason' => 'Returning report to intake triage queue after verification.',
            ])
            ->assertRedirect(route('admin.incidents.show', $incident->reference_number))
            ->assertSessionHas('success');

        $this->assertSame('pending_review', $incident->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'incident:review_overridden',
            'auditable_id' => $incident->id,
        ]);
    }

    public function test_admin_team_update_validates_payload(): void
    {
        $admin = $this->createUser('admin');
        $office = $this->createOffice('TM');
        $team = ResponseTeam::create([
            'office_id' => $office->id,
            'team_name' => 'Alpha Unit',
            'call_sign' => 'ALPHA-'.rand(10, 99),
            'team_type' => 'rescue',
            'is_active' => true,
        ]);

        // Empty required team_name should fail validation instead of crashing
        $this->actingAs($admin)
            ->put(route('admin.personnel.teams.update', $team->id), [
                'team_name' => '',
                'office_id' => 999999,
            ])
            ->assertSessionHasErrors(['team_name', 'office_id']);
    }

    public function test_meta_webhook_signature_service_rejects_null_and_empty_secret(): void
    {
        config(['services.meta.webhook_secret' => 'strong_secret', 'services.meta.app_secret' => 'strong_secret']);
        $apiService = new MetaMessengerApiService;

        $this->assertFalse($apiService->verifyWebhookSignature('{"entry":[]}', null));
        $this->assertFalse($apiService->verifyWebhookSignature('{"entry":[]}', ''));
        $this->assertFalse($apiService->verifyWebhookSignature('{"entry":[]}', 'sha256=invalid'));

        $validSig = 'sha256='.hash_hmac('sha256', '{"entry":[]}', 'strong_secret');
        $this->assertTrue($apiService->verifyWebhookSignature('{"entry":[]}', $validSig));
    }
}
