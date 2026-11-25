<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Office;
use App\Models\OfficeLocation;
use App\Models\OfficeUser;
use App\Models\PersonnelLocation;
use App\Models\PersonnelProfile;
use App\Models\Role;
use App\Models\SosLog;
use App\Models\User;
use App\Services\AdminIncidentService;
use App\Services\CitizenIncidentService;
use App\Services\EmergencyAssessmentService;
use App\Services\IncidentRoutingService;
use App\Services\InternalMessengerService;
use App\Services\LiveOperationsMapService;
use App\Services\PwaSyncService;
use App\Services\SosAlertService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class SystemAuditRepairsTest extends TestCase
{
    use DatabaseTransactions;

    private function createAccount(string $accountType, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'account_type' => $accountType,
            'is_active' => true,
            'phone_number' => '0917'.random_int(1000000, 9999999),
        ], $overrides));

        $role = Role::where('name', $accountType)->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        return $user;
    }

    private function createOffice(string $prefix = 'AUD'): Office
    {
        $code = strtoupper($prefix.'-'.Str::random(4));

        return Office::create([
            'name' => 'Audit Office '.$code,
            'code' => $code,
            'department_type' => 'disaster',
            'contact_phone' => '0917'.random_int(1000000, 9999999),
            'contact_email' => strtolower($code).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);
    }

    public function test_submitted_simplified_citizen_report_appears_in_incoming_queues_and_cannot_bypass_assessment(): void
    {
        $superAdmin = $this->createAccount('super_admin');
        $admin = $this->createAccount('admin');
        $citizen = $this->createAccount('citizen');

        /** @var CitizenIncidentService $citizenService */
        $citizenService = app(CitizenIncidentService::class);

        $result = $citizenService->submitSimplifiedIncident($citizen, [
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'latitude' => 9.5912,
            'longitude' => 122.4671,
            'accuracy' => 12.0,
            'location_source' => 'gps_device',
        ]);
        $incident = $result['incident'];

        $this->assertSame('submitted', $incident->status);
        $this->assertTrue(Incident::incomingReports()->whereKey($incident->id)->exists());

        $this->actingAs($admin)
            ->get(route('admin.incoming.index'))
            ->assertOk()
            ->assertSee($incident->reference_number);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.incoming.index'))
            ->assertOk()
            ->assertSee($incident->reference_number);

        // Even if status were manually forced to accepted, direct dispatch without category is blocked
        $incident->update(['status' => 'accepted']);

        /** @var IncidentRoutingService $routingService */
        $routingService = app(IncidentRoutingService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('category');
        $routingService->dispatchIncident($admin, $incident->fresh());
    }

    public function test_office_reviewer_can_review_submitted_incident(): void
    {
        $office = $this->createOffice('REV');
        $officeAdmin = $this->createAccount('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $officeAdmin->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        $citizen = $this->createAccount('citizen');
        $category = IncidentCategory::firstOrFail();

        $incident = Incident::create([
            'reference_number' => 'INC-AUD-'.strtoupper(Str::random(6)),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'status' => 'submitted',
            'workflow_stage' => 'new_report',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        /** @var AdminIncidentService $adminIncidentService */
        $adminIncidentService = app(AdminIncidentService::class);
        $review = $adminIncidentService->reviewIncident($officeAdmin, $incident, 'accepted', null, 'Reviewed by office');

        $this->assertSame('accepted', $review->decision);
        $this->assertSame('accepted', $incident->fresh()->status);
    }

    public function test_personnel_sos_succeeds_with_latest_location_rank_and_office_admin_notification(): void
    {
        $office = $this->createOffice('SOS');
        $officeAdmin = $this->createAccount('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $officeAdmin->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        $personnelUser = $this->createAccount('personnel');
        $profile = PersonnelProfile::create([
            'user_id' => $personnelUser->id,
            'office_id' => $office->id,
            'badge_number' => 'BDG-'.strtoupper(Str::random(6)),
            'rank_designation' => 'Senior Fire Officer',
            'specialization' => 'Fire Suppression',
            'is_team_leader' => true,
        ]);

        PersonnelLocation::create([
            'personnel_id' => $profile->id,
            'latitude' => 9.59310000,
            'longitude' => 122.46820000,
            'recorded_at' => now()->subMinutes(5),
            'created_at' => now()->subMinutes(5),
        ]);

        /** @var SosAlertService $sosService */
        $sosService = app(SosAlertService::class);
        $sos = $sosService->createPersonnelSos($personnelUser, [
            'idempotency_key' => 'sos-test-'.Str::uuid(),
        ]);

        $this->assertSame('last_known_stale', $sos->location_status);
        $this->assertSame('Senior Fire Officer', $sos->position_role);
        $this->assertEquals(9.5931, round((float) $sos->latitude, 4));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $officeAdmin->id,
            'type' => 'personnel_sos_alert',
        ]);

        SosLog::create([
            'sos_alert_id' => $sos->id,
            'actor_id' => $officeAdmin->id,
            'action_type' => 'acknowledged',
            'notes' => 'Backup team notified cleanly.',
            'created_at' => now(),
        ]);

        $admin = $this->createAccount('admin');
        $this->actingAs($admin)
            ->get(route('admin.sos.show', $sos->id))
            ->assertOk()
            ->assertSee('Backup team notified cleanly.');
    }

    public function test_office_admin_with_manage_accounts_can_create_office_staff(): void
    {
        $office = $this->createOffice('STF');
        $officeAdmin = $this->createAccount('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $officeAdmin->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
            'can_manage_accounts' => true,
        ]);

        $staffEmail = 'staff.'.Str::random(6).'@hinobaan.gov.ph';
        $response = $this->actingAs($officeAdmin)
            ->from(route('office.personnel'))
            ->post(route('office.staff.store'), [
                'name' => 'Permitted Office Staff',
                'email' => $staffEmail,
                'phone_number' => '0918'.random_int(1000000, 9999999),
                'password' => 'OfficeStaff#2026',
                'office_level' => 'office_staff',
                'can_review_incidents' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('office.personnel'));
        $this->assertDatabaseHas('users', [
            'email' => $staffEmail,
            'account_type' => 'office',
        ]);

        $staffUser = User::where('email', $staffEmail)->firstOrFail();
        $this->actingAs($staffUser)->post(route('office.staff.store'), [
            'name' => 'Unauthorized Nested Staff',
            'email' => 'unauth.'.Str::random(5).'@hinobaan.gov.ph',
            'phone_number' => '0919'.random_int(1000000, 9999999),
            'password' => 'OfficeStaff#2026',
            'office_level' => 'office_staff',
        ])->assertForbidden();
    }

    public function test_super_admin_can_open_messages_and_is_added_to_incident_threads(): void
    {
        $superAdmin = $this->createAccount('super_admin');
        $office = $this->createOffice('MSG');
        $officeUser = $this->createAccount('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $officeUser->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.messages.index'))
            ->assertOk();

        $citizen = $this->createAccount('citizen');
        $category = IncidentCategory::firstOrFail();
        $incident = Incident::create([
            'reference_number' => 'INC-MSG-'.strtoupper(Str::random(6)),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'status' => 'accepted',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);
        IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'assigned_by' => $superAdmin->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        /** @var InternalMessengerService $messengerService */
        $messengerService = app(InternalMessengerService::class);
        $conversation = $messengerService->getOrCreateIncidentConversation($officeUser, $incident);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_citizen_phone_verification_accessor_resets_when_phone_changes(): void
    {
        $citizen = $this->createAccount('citizen', [
            'phone_verified_at' => now(),
        ]);

        $this->assertTrue($citizen->is_phone_verified);

        /** @var CitizenIncidentService $citizenService */
        $citizenService = app(CitizenIncidentService::class);
        $citizenService->updateCitizenContactProfile($citizen, [
            'name' => 'Updated Citizen Name',
            'phone_number' => '0998'.random_int(1000000, 9999999),
        ]);

        $citizen->refresh();
        $this->assertNull($citizen->phone_verified_at);
        $this->assertFalse($citizen->is_phone_verified);
    }

    public function test_assessment_notifies_office_admin_on_coverage_gap_without_sql_error(): void
    {
        $admin = $this->createAccount('admin');
        $office = $this->createOffice('GAP');
        $officeAdmin = $this->createAccount('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $officeAdmin->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        $citizen = $this->createAccount('citizen');
        $category = IncidentCategory::firstOrFail();
        $barangay = Barangay::firstOrFail();

        $incident = Incident::create([
            'reference_number' => 'INC-GAP-'.strtoupper(Str::random(6)),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'status' => 'submitted',
            'workflow_stage' => 'new_report',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        /** @var EmergencyAssessmentService $assessmentService */
        $assessmentService = app(EmergencyAssessmentService::class);
        $assessmentService->confirmAssessmentAndDeploy($admin, $incident, [
            'category_id' => $category->id,
            'priority' => 'high',
            'incident_description' => 'Verified coverage gap incident requiring manual escalation.',
            'verified_barangay_id' => $barangay->id,
            'required_office_ids' => [$office->id],
            'required_qualifications' => ['Swiftwater'],
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $officeAdmin->id,
            'type' => 'coverage_warning',
        ]);
    }

    public function test_office_map_includes_direct_personnel_and_real_office_coordinates(): void
    {
        $barangay = Barangay::firstOrFail();
        $office = $this->createOffice('MAP');
        OfficeLocation::create([
            'office_id' => $office->id,
            'barangay_id' => $barangay->id,
            'street_address' => 'Poblacion Rescue Station',
            'latitude' => 9.58880000,
            'longitude' => 122.46660000,
            'service_radius_meters' => 1500,
        ]);

        $personnelUser = $this->createAccount('personnel');
        $profile = PersonnelProfile::create([
            'user_id' => $personnelUser->id,
            'office_id' => $office->id,
            'badge_number' => 'BDG-MAP-'.strtoupper(Str::random(4)),
            'rank_designation' => 'EMT',
            'is_team_leader' => false,
        ]);

        /** @var LiveOperationsMapService $mapService */
        $mapService = app(LiveOperationsMapService::class);
        $data = $mapService->getOfficeMapData($office);

        $responderIds = collect($data['responders'])->pluck('id');
        $this->assertTrue($responderIds->contains($profile->id));

        $officeData = collect($data['offices'])->firstWhere('id', $office->id);
        $this->assertNotNull($officeData);
        $this->assertEquals(9.5888, round((float) $officeData['latitude'], 4));
        $this->assertEquals(122.4666, round((float) $officeData['longitude'], 4));
    }

    public function test_pwa_milestone_sync_creates_missing_personnel_availability(): void
    {
        $office = $this->createOffice('PWA');
        $personnelUser = $this->createAccount('personnel');
        $profile = PersonnelProfile::create([
            'user_id' => $personnelUser->id,
            'office_id' => $office->id,
            'badge_number' => 'BDG-PWA-'.strtoupper(Str::random(4)),
            'rank_designation' => 'Responder',
            'is_team_leader' => false,
        ]);

        $citizen = $this->createAccount('citizen');
        $category = IncidentCategory::firstOrFail();
        $incident = Incident::create([
            'reference_number' => 'INC-PWA-'.strtoupper(Str::random(6)),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'status' => 'dispatched',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);
        IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 9.5910,
            'longitude' => 122.4650,
            'location_source' => 'gps_device',
        ]);
        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'assigned_by' => $personnelUser->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        /** @var PwaSyncService $syncService */
        $syncService = app(PwaSyncService::class);
        $result = $syncService->syncPersonnelMilestone(
            $personnelUser,
            $assignment->id,
            'on_the_way',
            'En route to incident scene',
            9.5910,
            122.4650,
            1,
            'ms-'.Str::uuid()
        );

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'dispatched',
        ]);
    }
}
