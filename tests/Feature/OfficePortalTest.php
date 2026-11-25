<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\IncidentRoutingRule;
use App\Models\IncidentStatusHistory;
use App\Models\Office;
use App\Models\OfficeLocation;
use App\Models\OfficeResponsibility;
use App\Models\OfficeUser;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\IncidentRoutingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OfficePortalTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        return $admin;
    }

    private function createOffice(string $code = 'TEST-OFFICE'): Office
    {
        $uniqueCode = $code.'-'.uniqid();
        $barangay = Barangay::first();
        $office = Office::create([
            'name' => 'Agency '.$uniqueCode,
            'code' => $uniqueCode,
            'department_type' => 'disaster',
            'description' => 'Test emergency response office agency.',
            'contact_phone' => '09171112222',
            'contact_email' => strtolower($uniqueCode).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);

        OfficeLocation::create([
            'office_id' => $office->id,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'street_address' => 'National Highway, Poblacion',
            'latitude' => 9.4975,
            'longitude' => 122.5850,
            'service_radius_meters' => 5000,
        ]);

        return $office;
    }

    private function createOfficeUser(Office $office, string $level = 'office_staff', bool $canReview = false, bool $canDispatch = false): User
    {
        $user = User::factory()->create([
            'account_type' => 'office',
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
        $role = Role::where('name', 'office')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'office_level' => $level,
            'can_review_incidents' => $canReview,
            'can_dispatch_units' => $canDispatch,
        ]);

        UserPresence::create([
            'user_id' => $user->id,
            'status' => 'online',
            'last_heartbeat_at' => now(),
        ]);

        return $user;
    }

    private function createIncident(array $attributes = []): Incident
    {
        $category = IncidentCategory::first();
        $barangay = Barangay::first();
        $citizen = User::factory()->create(['account_type' => 'citizen']);

        $incident = Incident::create(array_merge([
            'reference_number' => 'INC-2026-'.rand(1000, 9999).'-TEST',
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category ? $category->id : 1,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'description' => 'Test emergency incident description.',
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'status' => 'pending_review',
            'priority' => 'critical',
            'submitted_at' => now(),
            'version_lock' => 1,
        ], $attributes));

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'barangay_id' => $incident->barangay_id,
            'street_name' => 'National Highway',
            'latitude' => 9.4975,
            'longitude' => 122.5850,
            'accuracy' => 10.0,
            'location_source' => 'gps_device',
        ]);

        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'previous_status' => 'submitted',
            'new_status' => $incident->status,
            'changed_by' => $citizen->id,
            'created_at' => now(),
        ]);

        return $incident;
    }

    /**
     * Requirement 1: Admin can create office agency with location and categories.
     */
    public function test_admin_can_create_office(): void
    {
        $admin = $this->createAdmin();
        $barangay = Barangay::first();
        $code = 'OF-'.rand(100, 999);

        $response = $this->actingAs($admin)->post(route('admin.offices.store'), [
            'name' => 'Bureau of Fire Protection Outpost',
            'code' => $code,
            'department_type' => 'fire',
            'description' => 'Firefighting and hazmat operations agency.',
            'contact_phone' => '09170001111',
            'contact_email' => strtolower($code).'@hinobaan.gov.ph',
            'barangay_id' => $barangay ? $barangay->id : 1,
            'street_address' => 'Crossing Bacuyangan',
            'latitude' => 9.4800,
            'longitude' => 122.5700,
        ]);

        $response->assertRedirect(route('admin.offices.index'));
        $this->assertDatabaseHas('offices', ['code' => $code, 'department_type' => 'fire']);
        $this->assertDatabaseHas('office_locations', ['street_address' => 'Crossing Bacuyangan']);
    }

    /**
     * Requirement 2: Admin can create Office Admin account with review permissions.
     */
    public function test_admin_can_create_office_admin(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('ADM-'.rand(10, 99));

        $email = 'admin.'.time().'@hinobaan.gov.ph';
        $response = $this->actingAs($admin)->post(route('admin.offices.users.store', $office->id), [
            'name' => 'Commander Juan Santos',
            'email' => $email,
            'phone_number' => '0917'.rand(1000000, 9999999),
            'password' => 'SecurePass123!',
            'office_level' => 'office_admin',
            'can_review_incidents' => '1',
            'can_dispatch_units' => '1',
        ]);

        $response->assertRedirect(route('admin.offices.users', $office->id));
        $this->assertDatabaseHas('users', ['email' => $email, 'account_type' => 'office']);
        $this->assertDatabaseHas('office_users', [
            'office_id' => $office->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
        ]);
    }

    /**
     * Requirement 3: Admin can create Office Staff account with restricted permissions.
     */
    public function test_admin_can_create_office_staff(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('STF-'.rand(10, 99));

        $email = 'staff.'.time().'@hinobaan.gov.ph';
        $response = $this->actingAs($admin)->post(route('admin.offices.users.store', $office->id), [
            'name' => 'Desk Officer Maria',
            'email' => $email,
            'phone_number' => '0917'.rand(1000000, 9999999),
            'password' => 'SecurePass123!',
            'office_level' => 'office_staff',
            'can_review_incidents' => '0',
            'can_dispatch_units' => '0',
        ]);

        $response->assertRedirect(route('admin.offices.users', $office->id));
        $this->assertDatabaseHas('users', ['email' => $email, 'account_type' => 'office']);
        $this->assertDatabaseHas('office_users', [
            'office_id' => $office->id,
            'office_level' => 'office_staff',
            'can_review_incidents' => false,
        ]);
    }

    /**
     * Requirement 4: Admin can update office user permissions.
     */
    public function test_admin_can_update_office_permissions(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('PRM-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff', false, false);

        $response = $this->actingAs($admin)->put(route('admin.offices.users.update', [$office->id, $user->id]), [
            'name' => 'Promoted Commander',
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'office_level' => 'office_admin',
            'can_review_incidents' => '1',
            'can_dispatch_units' => '1',
        ]);

        $response->assertRedirect(route('admin.offices.users', $office->id));
        $this->assertDatabaseHas('office_users', [
            'office_id' => $office->id,
            'user_id' => $user->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
        ]);
    }

    /**
     * Requirement 5: Office user can view scoped incidents.
     */
    public function test_office_user_can_view_scoped_incidents(): void
    {
        $office = $this->createOffice('MON-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff');

        $incident = $this->createIncident();

        // Assign to this office
        IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('office.incidents'));
        $response->assertOk();
        $response->assertViewHas('incidents');
    }

    /**
     * Requirement 6: Unauthorized cross-office access is prevented (403 Forbidden).
     */
    public function test_unauthorized_cross_office_access_is_prevented(): void
    {
        $officeA = $this->createOffice('OFA-'.rand(10, 99));
        $officeB = $this->createOffice('OFB-'.rand(10, 99));

        $userA = $this->createOfficeUser($officeA, 'office_staff');
        $incidentB = $this->createIncident();

        // Assign exclusively to Office B
        IncidentAssignment::create([
            'incident_id' => $incidentB->id,
            'office_id' => $officeB->id,
            'assigned_by' => $userA->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        // User A attempts to view Office B incident details -> 403 Forbidden
        $response = $this->actingAs($userA)->get(route('office.incidents.show', $incidentB->reference_number));
        $response->assertForbidden();
    }

    /**
     * Requirement 7: Authorized Office Admin can accept incident within scope.
     */
    public function test_authorized_office_admin_can_accept_incident(): void
    {
        $category = IncidentCategory::first();
        $office = $this->createOffice('REV-'.rand(10, 99));
        $adminUser = $this->createOfficeUser($office, 'office_admin', true, true);

        // Map office responsibility
        OfficeResponsibility::create([
            'office_id' => $office->id,
            'category_slug' => $category->slug,
        ]);

        $incident = $this->createIncident(['category_id' => $category->id, 'status' => 'pending_review']);

        $response = $this->actingAs($adminUser)->post(route('office.incidents.review', $incident->reference_number), [
            'action' => 'accept',
            'review_remarks' => 'Intake confirmed by agency commander.',
        ]);

        $response->assertRedirect(route('office.incidents'));
        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('incident_reviews', [
            'incident_id' => $incident->id,
            'reviewed_by' => $adminUser->id,
            'decision' => 'accepted',
        ]);
    }

    /**
     * Requirement 8: Authorized Office Admin can reject incident with required reason.
     */
    public function test_authorized_office_admin_can_reject_incident(): void
    {
        $category = IncidentCategory::first();
        $office = $this->createOffice('REJ-'.rand(10, 99));
        $adminUser = $this->createOfficeUser($office, 'office_admin', true, true);

        OfficeResponsibility::create([
            'office_id' => $office->id,
            'category_slug' => $category->slug,
        ]);

        $incident = $this->createIncident(['category_id' => $category->id, 'status' => 'pending_review']);

        $response = $this->actingAs($adminUser)->post(route('office.incidents.review', $incident->reference_number), [
            'action' => 'reject',
            'rejection_category' => 'duplicate',
            'rejection_reason' => 'Duplicate call logged for same national highway incident.',
        ]);

        $response->assertRedirect(route('office.incidents'));
        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Requirement 9: Office Staff cannot review or accept incidents (403 Forbidden).
     */
    public function test_office_staff_cannot_review_or_accept_incidents(): void
    {
        $office = $this->createOffice('STF-DENY-'.rand(10, 99));
        $staffUser = $this->createOfficeUser($office, 'office_staff', false, false);

        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($staffUser)->post(route('office.incidents.review', $incident->reference_number), [
            'action' => 'accept',
        ]);

        $response->assertForbidden();
    }

    /**
     * Requirement 10: Office routing configuration connects with routing engine.
     */
    public function test_office_routing_configuration(): void
    {
        $category = IncidentCategory::first();
        $barangay = Barangay::first();
        $office = $this->createOffice('ROUT-'.rand(10, 99));

        // Create routing rule pointing category to this office
        IncidentRoutingRule::create([
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'target_office_id' => $office->id,
            'is_active' => true,
        ]);

        $incident = $this->createIncident(['category_id' => $category->id, 'barangay_id' => $barangay->id]);

        /** @var IncidentRoutingService $routing */
        $routing = app(IncidentRoutingService::class);
        $recommendation = $routing->recommendRouting($incident);

        $this->assertEquals($office->id, $recommendation['recommended_office']->id);
    }

    /**
     * Requirement 11: Office incident map access.
     */
    public function test_office_map_access(): void
    {
        $office = $this->createOffice('MAP-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff');

        $response = $this->actingAs($user)->get(route('office.map'));
        $response->assertOk();
        $response->assertViewHas('mapData');
    }

    /**
     * Requirement 12: Office personnel monitoring displays live status.
     */
    public function test_office_personnel_monitoring(): void
    {
        $office = $this->createOffice('MON-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_admin');

        $response = $this->actingAs($user)->get(route('office.dashboard'));
        $response->assertOk();
        $response->assertViewHas('stats');
        $response->assertViewHas('onlinePersonnel');
    }

    /**
     * Requirement 13: Internal Messenger access for office users.
     */
    public function test_office_messages_access(): void
    {
        $office = $this->createOffice('MSG-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff');

        $response = $this->actingAs($user)->get(route('office.messages'));
        $response->assertOk();
        $response->assertViewHas('conversations');
    }

    /**
     * Requirement 14: Office records & reports access.
     */
    public function test_office_records_and_reports_access(): void
    {
        $office = $this->createOffice('REC-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff');

        $response = $this->actingAs($user)->get(route('office.records'));
        $response->assertOk();
        $response->assertViewHas('records');
    }

    /**
     * Requirement 15: Office records CSV report export.
     */
    public function test_office_records_csv_export(): void
    {
        $office = $this->createOffice('EXP-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_admin');

        $response = $this->actingAs($user)->get(route('office.records.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    /**
     * Requirement 16: Office user status toggle.
     */
    public function test_office_user_status_toggle(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('TOG-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff');

        $response = $this->actingAs($admin)->post(route('admin.offices.users.toggle-status', [$office->id, $user->id]));
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    /**
     * Requirement 17: Office user password reset securely.
     */
    public function test_office_user_password_reset(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('RST-'.rand(10, 99));
        $user = $this->createOfficeUser($office, 'office_staff');

        $response = $this->actingAs($admin)->post(route('admin.offices.users.reset-password', [$office->id, $user->id]), [
            'password' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
    }

    /**
     * Requirement 18: Admin office management directory displays real metrics.
     */
    public function test_admin_office_management_index_displays_metrics(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('DIR-'.rand(10, 99));

        $response = $this->actingAs($admin)->get(route('admin.offices.index'));
        $response->assertOk();
        $response->assertViewHas('offices');
        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_offices', $stats);
        $this->assertArrayHasKey('active_offices', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['total_offices']);
    }
}
