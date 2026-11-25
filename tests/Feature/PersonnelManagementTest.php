<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use App\Models\Office;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelLocation;
use App\Models\PersonnelProfile;
use App\Models\ResponseTeam;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\IncidentRoutingService;
use App\Services\PersonnelManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PersonnelManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
            'phone_number' => '0917'.substr(str_replace('.', '', uniqid('', true)), -7),
        ]);
        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        return $admin;
    }

    private function createPersonnel(Office $office, string $opStatus = 'available'): PersonnelProfile
    {
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
            'phone_number' => '0917'.substr(str_replace('.', '', uniqid('', true)), -7),
        ]);
        $role = Role::where('name', 'personnel')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'BADGE-'.strtoupper(substr(uniqid('', true), -8)),
            'rank_designation' => 'Field Responder',
            'specialization' => 'First Aid',
        ]);

        PersonnelAvailability::create([
            'personnel_id' => $profile->id,
            'operational_status' => $opStatus,
            'status_reason' => 'Unit Initialization',
            'status_updated_at' => now(),
        ]);

        UserPresence::create([
            'user_id' => $user->id,
            'status' => $opStatus === 'available' ? 'online' : 'offline',
            'last_heartbeat_at' => now(),
        ]);

        return $profile;
    }

    private function createIncident(Barangay $barangay, IncidentCategory $category, User $citizen): Incident
    {
        $incident = Incident::create([
            'reference_number' => 'INC-TEST-'.strtoupper(substr(uniqid('', true), -8)),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'description' => 'Urgent rescue and medical assistance needed at scene.',
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'status' => 'accepted',
            'priority' => 'critical',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'barangay_id' => $barangay->id,
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
     * Requirement 1: Admin can register personnel account with credentials and profile.
     */
    public function test_admin_can_register_personnel_account(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first() ?? Office::create(['name' => 'MDRRMO HQ', 'code' => 'MDRRMO-HQ', 'office_type' => 'main']);
        $barangay = Barangay::first();

        $response = $this->actingAs($admin)->post(route('admin.personnel.store'), [
            'name' => 'Alex Dela Cruz',
            'email' => 'alex.'.time().'@hinobaan.gov.ph',
            'phone_number' => '09171234567',
            'password' => 'SecurePass123!',
            'office_id' => $office->id,
            'badge_number' => 'MDR-'.strtoupper(substr(uniqid('', true), -8)),
            'username' => 'alex_responder',
            'rank_designation' => 'Paramedic Lead',
            'specialization' => 'Trauma Care',
            'is_team_leader' => '1',
            'barangay_ids' => $barangay ? [$barangay->id] : [],
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $this->assertDatabaseHas('users', ['email' => 'alex.'.time().'@hinobaan.gov.ph', 'account_type' => 'personnel']);
        $this->assertDatabaseHas('personnel_profiles', ['username' => 'alex_responder', 'rank_designation' => 'Paramedic Lead']);
    }

    /**
     * Requirement 2: Admin can update personnel profile.
     */
    public function test_admin_can_update_personnel_profile(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $profile = $this->createPersonnel($office);

        $response = $this->actingAs($admin)->put(route('admin.personnel.update', $profile->id), [
            'name' => 'Updated Officer Name',
            'email' => $profile->user->email,
            'phone_number' => '09189998888',
            'office_id' => $office->id,
            'badge_number' => $profile->badge_number,
            'username' => 'updated_handle',
            'rank_designation' => 'Senior Field Officer',
            'specialization' => 'High Angle Rescue',
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $this->assertDatabaseHas('users', ['id' => $profile->user_id, 'name' => 'Updated Officer Name', 'phone_number' => '09189998888']);
        $this->assertDatabaseHas('personnel_profiles', ['id' => $profile->id, 'rank_designation' => 'Senior Field Officer']);
    }

    /**
     * Requirement 3: Admin can create response team.
     */
    public function test_admin_can_create_response_team(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();

        $response = $this->actingAs($admin)->post(route('admin.personnel.teams.store'), [
            'team_name' => 'Delta Swift Rescue',
            'call_sign' => 'DELTA-'.strtoupper(substr(uniqid('', true), -6)),
            'office_id' => $office->id,
            'team_type' => 'Rescue',
            'description' => 'Swift flood and swiftwater rescue detachment.',
        ]);

        $response->assertRedirect(route('admin.personnel.teams'));
        $this->assertDatabaseHas('response_teams', ['team_name' => 'Delta Swift Rescue', 'team_type' => 'Rescue']);
    }

    /**
     * Requirement 4: Admin can assign team membership.
     */
    public function test_admin_can_assign_team_membership(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $profile = $this->createPersonnel($office);
        $team = ResponseTeam::create([
            'office_id' => $office->id,
            'team_name' => 'Alpha Taskforce',
            'call_sign' => 'ALPHA-'.strtoupper(substr(uniqid('', true), -6)),
            'team_type' => 'Medical',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.personnel.update', $profile->id), [
            'name' => $profile->user->name,
            'email' => $profile->user->email,
            'phone_number' => $profile->user->phone_number,
            'office_id' => $office->id,
            'badge_number' => $profile->badge_number,
            'rank_designation' => $profile->rank_designation,
            'team_ids' => [$team->id],
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $this->assertDatabaseHas('team_memberships', ['personnel_id' => $profile->id, 'team_id' => $team->id]);
    }

    /**
     * Requirement 5: Admin can assign personnel jurisdictions.
     */
    public function test_admin_can_assign_personnel_jurisdictions(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $profile = $this->createPersonnel($office);
        $barangay = Barangay::first();

        $response = $this->actingAs($admin)->put(route('admin.personnel.update', $profile->id), [
            'name' => $profile->user->name,
            'email' => $profile->user->email,
            'phone_number' => $profile->user->phone_number,
            'office_id' => $office->id,
            'badge_number' => $profile->badge_number,
            'rank_designation' => $profile->rank_designation,
            'barangay_ids' => [$barangay->id],
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $this->assertDatabaseHas('personnel_jurisdictions', ['personnel_id' => $profile->id, 'barangay_id' => $barangay->id]);
    }

    /**
     * Requirement 6: Personnel can switch to online availability.
     */
    public function test_personnel_can_switch_to_online_availability(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'off_duty');

        $response = $this->actingAs($profile->user)->post(route('personnel.availability.update'), [
            'status' => 'online',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));
        $this->assertDatabaseHas('personnel_availabilities', ['personnel_id' => $profile->id, 'operational_status' => 'available']);
        $this->assertDatabaseHas('user_presences', ['user_id' => $profile->user_id, 'status' => 'online']);
    }

    /**
     * Requirement 7: Personnel can switch to offline availability.
     */
    public function test_personnel_can_switch_to_offline_availability(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        $response = $this->actingAs($profile->user)->post(route('personnel.availability.update'), [
            'status' => 'offline',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));
        $this->assertDatabaseHas('personnel_availabilities', ['personnel_id' => $profile->id, 'operational_status' => 'off_duty']);
        $this->assertDatabaseHas('user_presences', ['user_id' => $profile->user_id, 'status' => 'offline']);
    }

    /**
     * Requirement 8: Stale heartbeat triggers connection loss detection (>2 mins).
     */
    public function test_stale_heartbeat_triggers_connection_loss_detection(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        // Set presence to online but heartbeat 5 minutes ago
        UserPresence::where('user_id', $profile->user_id)->update([
            'status' => 'online',
            'last_heartbeat_at' => now()->subMinutes(5),
        ]);

        /** @var PersonnelManagementService $service */
        $service = app(PersonnelManagementService::class);
        $lostCount = $service->checkConnectionLoss(2);

        $this->assertGreaterThanOrEqual(1, $lostCount);
        $this->assertDatabaseHas('user_presences', ['user_id' => $profile->user_id, 'status' => 'connection_lost']);
    }

    /**
     * Requirement 9: Active assignment preserved when going offline.
     * Setting offline disables new dispatch availability while preserving all active task relationships.
     */
    public function test_active_assignment_preserved_when_going_offline(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');
        $citizen = User::factory()->create(['account_type' => 'citizen']);
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = $this->createIncident($barangay, $category, $citizen);

        // Assign personnel actively
        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'assigned_by' => $citizen->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        // Switch offline via HTTP gateway
        $response = $this->actingAs($profile->user)->post(route('personnel.availability.update'), [
            'status' => 'offline',
        ]);

        // Navigates to dashboard with success message
        $response->assertRedirect(route('personnel.dashboard'));
        $response->assertSessionHas('success');

        // Operational status is set to off_duty (disabling new dispatches)
        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'off_duty',
        ]);

        // Active assignment relationship is completely preserved
        $this->assertDatabaseHas('incident_assignments', [
            'id' => $assignment->id,
            'personnel_id' => $profile->id,
            'status' => 'active',
        ]);
    }

    /**
     * Requirement 10: Routing engine filters by personnel jurisdiction and team.
     */
    public function test_routing_engine_filters_by_personnel_jurisdiction_and_team(): void
    {
        $barangay = Barangay::first();
        $category = IncidentCategory::first();
        $citizen = User::factory()->create(['account_type' => 'citizen']);

        $incident = $this->createIncident($barangay, $category, $citizen);

        /** @var IncidentRoutingService $routing */
        $routing = app(IncidentRoutingService::class);
        $initialRec = $routing->recommendRouting($incident);
        $targetOffice = $initialRec['recommended_office'] ?? Office::first();
        $targetTeam = $initialRec['recommended_team'] ?? $targetOffice->teams()->first();

        // Responder A available and in recommended office/team
        $profileA = $this->createPersonnel($targetOffice, 'available');
        if ($targetTeam) {
            $profileA->teams()->syncWithoutDetaching([$targetTeam->id => ['role_in_team' => 'member', 'assigned_at' => now()]]);
        }

        // Responder B off duty
        $profileB = $this->createPersonnel($targetOffice, 'off_duty');

        $recommendation = $routing->recommendRouting($incident);

        $recommendedPersonnelIds = $recommendation['eligible_personnel']->pluck('id')->toArray();
        $this->assertContains($profileA->id, $recommendedPersonnelIds);
        $this->assertNotContains($profileB->id, $recommendedPersonnelIds);
    }

    /**
     * Requirement 11: Personnel receives assignment notification upon dispatch.
     */
    public function test_personnel_receives_assignment_notification(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');
        $citizen = User::factory()->create(['account_type' => 'citizen']);
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = $this->createIncident($barangay, $category, $citizen);

        // Assign personnel
        $this->actingAs($admin)->post(route('admin.incidents.assign', $incident->reference_number), [
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'dispatch_notes' => 'Immediate deployment to highway crossing.',
        ]);

        // Dispatch incident
        $this->actingAs($admin)->post(route('admin.incidents.dispatch', $incident->reference_number), [
            'dispatch_notes' => 'Authorized dispatch order.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $profile->user_id,
            'type' => 'incident_dispatch_order',
        ]);
    }

    /**
     * Requirement 12: Duty location tracking records GPS coordinates.
     */
    public function test_duty_location_tracking_records_gps_coordinates(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        /** @var PersonnelManagementService $service */
        $service = app(PersonnelManagementService::class);
        $loc = $service->recordPersonnelLocation($profile, 9.4975, 122.5850, 4.5, 30.0, 90.0);

        $this->assertInstanceOf(PersonnelLocation::class, $loc);
        $this->assertDatabaseHas('personnel_locations', [
            'personnel_id' => $profile->id,
            'latitude' => 9.4975,
            'longitude' => 122.5850,
        ]);
    }

    /**
     * Requirement 13: Duty location broadcasts authorized updates via JSON endpoint.
     */
    public function test_duty_location_broadcasts_authorized_updates(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        $response = $this->actingAs($profile->user)->postJson(route('personnel.location'), [
            'latitude' => 9.5012,
            'longitude' => 122.5891,
            'accuracy' => 5.0,
            'speed' => 25.5,
            'heading' => 180.0,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'recorded_at']);
        $this->assertDatabaseHas('personnel_locations', [
            'personnel_id' => $profile->id,
            'latitude' => 9.5012,
            'longitude' => 122.5891,
        ]);
    }

    /**
     * Requirement 14: Admin can create and manage response bases.
     */
    public function test_admin_can_create_and_manage_response_bases(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $barangay = Barangay::first();

        $response = $this->actingAs($admin)->post(route('admin.personnel.bases.store'), [
            'base_name' => 'Hinoba-an South Coastal Post',
            'office_id' => $office->id,
            'barangay_id' => $barangay->id,
            'address' => 'Sitio Bacuyangan Coast Road',
            'latitude' => 9.4500,
            'longitude' => 122.5500,
            'contact_phone' => '09191112222',
        ]);

        $response->assertRedirect(route('admin.personnel.bases'));
        $this->assertDatabaseHas('response_bases', [
            'base_name' => 'Hinoba-an South Coastal Post',
            'contact_phone' => '09191112222',
        ]);
    }

    /**
     * Requirement 15: Personnel portal access permissions (authorized personnel access).
     */
    public function test_personnel_portal_access_permissions(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        $this->actingAs($profile->user)->get(route('personnel.dashboard'))->assertOk();
        $this->actingAs($profile->user)->get(route('personnel.tasks'))->assertOk();
        $this->actingAs($profile->user)->get(route('personnel.active-incidents'))->assertOk();
        $this->actingAs($profile->user)->get(route('personnel.map'))->assertOk();
        $this->actingAs($profile->user)->get(route('personnel.records'))->assertOk();
    }

    /**
     * Requirement 16: Unauthorized personnel cannot access other portals.
     */
    public function test_unauthorized_personnel_cannot_access_other_portals(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        // Personnel accessing admin dashboard -> Redirected to authorized portal
        $this->actingAs($profile->user)->get(route('admin.dashboard'))->assertRedirect(route('personnel.availability'));
        // Personnel accessing admin personnel index -> Redirected to authorized portal
        $this->actingAs($profile->user)->get(route('admin.personnel.index'))->assertRedirect(route('personnel.availability'));
        // Personnel accessing office dashboard -> Redirected to authorized portal
        $this->actingAs($profile->user)->get(route('office.dashboard'))->assertRedirect(route('personnel.availability'));
    }

    /**
     * Requirement 17: Personnel can update incident milestones.
     */
    public function test_personnel_can_update_incident_milestones(): void
    {
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');
        $citizen = User::factory()->create(['account_type' => 'citizen']);
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = $this->createIncident($barangay, $category, $citizen);
        $incident->update(['status' => 'dispatched']);
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'previous_status' => 'accepted',
            'new_status' => 'dispatched',
            'changed_by' => $citizen->id,
            'created_at' => now(),
        ]);
        $incident->update(['status' => 'dispatched']);
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'previous_status' => 'accepted',
            'new_status' => 'dispatched',
            'changed_by' => $citizen->id,
            'created_at' => now(),
        ]);

        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'assigned_by' => $citizen->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        // Advance to acknowledged
        $response = $this->actingAs($profile->user)->post(route('personnel.milestone', $assignment->id), [
            'milestone' => 'acknowledged',
            'notes' => 'Call received and confirmed.',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('response_logs', [
            'assignment_id' => $assignment->id,
            'milestone' => 'acknowledged',
        ]);

        // Advance to on_scene
        $response2 = $this->actingAs($profile->user)->post(route('personnel.milestone', $assignment->id), [
            'milestone' => 'on_scene',
            'notes' => 'On scene at highway crossing.',
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('response_logs', [
            'assignment_id' => $assignment->id,
            'milestone' => 'on_scene',
        ]);
        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'on_scene',
        ]);
    }

    /**
     * Requirement 18: Admin availability monitor displays real metrics.
     */
    public function test_admin_availability_monitor_displays_real_metrics(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $profile = $this->createPersonnel($office, 'available');

        $response = $this->actingAs($admin)->get(route('admin.personnel.availability'));
        $response->assertOk();
        $response->assertViewHas('stats');
        $response->assertViewHas('personnel');

        $stats = $response->viewData('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('available', $stats);
        $this->assertArrayHasKey('dispatched', $stats);
        $this->assertArrayHasKey('on_scene', $stats);
        $this->assertArrayHasKey('off_duty', $stats);
        $this->assertArrayHasKey('connection_lost', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['total']);
    }
}
