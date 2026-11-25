<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginRedesignAndPersonnelStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_public_homepage_shows_only_citizen_portal(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Citizen Portal');
        $response->assertSee('Citizen Sign In');
        $response->assertSee('Submit Emergency Report (No Login)');
        $response->assertSee('TRIGGER EMERGENCY SOS');

        // Must NOT show staff or administrative portal selection links or buttons
        $response->assertDontSee('ALERTO System Portals');
        $response->assertDontSee('Personnel Portal');
        $response->assertDontSee('Office Portal');
        $response->assertDontSee('LDRRMO Super Admin');
        $response->assertDontSee('Super Admin &rarr;');
        $response->assertDontSee('Admin Sign In &rarr;');
        $response->assertDontSee('personnel.login');
        $response->assertDontSee('office.login');
    }

    public function test_public_login_route_shows_citizen_login(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Citizen Sign In');
        $response->assertSee('Citizen Portal');
        $response->assertDontSee('Super Admin');
        $response->assertDontSee('DIAL DAPH Command Center Operators Only');
    }

    public function test_citizen_can_login_via_citizen_portal(): void
    {
        $citizen = User::factory()->create([
            'account_type' => 'citizen',
            'email' => 'citizen.test.'.time().'@example.com',
            'phone_number' => '0917'.rand(1000000, 9999999),
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $response = $this->post(route('citizen.login.post'), [
            'login' => $citizen->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($citizen);
    }

    public function test_citizen_cannot_login_through_personnel_portal(): void
    {
        $citizen = User::factory()->create([
            'account_type' => 'citizen',
            'email' => 'citizen.blocked.'.time().'@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $response = $this->post(route('personnel.login.post'), [
            'login' => $citizen->email,
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_citizen_cannot_login_through_admin_or_super_admin_portal(): void
    {
        $citizen = User::factory()->create([
            'account_type' => 'citizen',
            'email' => 'citizen.adminblock.'.time().'@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $response1 = $this->post(route('admin.login.post'), [
            'login' => $citizen->email,
            'password' => 'Password123!',
        ]);
        $response1->assertSessionHasErrors('login');

        $response2 = $this->post(route('super-admin.login.post'), [
            'login' => $citizen->email,
            'password' => 'Password123!',
        ]);
        $response2->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_command_center_alias_routes_work_for_admin(): void
    {
        $response = $this->get(route('command-center.login'));
        $response->assertStatus(200);
        $response->assertSee('DIAL DAPH Command Center');

        $admin = User::factory()->create([
            'account_type' => 'admin',
            'email' => 'operator.'.time().'@dialdaph.gov.ph',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $postResponse = $this->post(route('command-center.login.post'), [
            'login' => $admin->email,
            'password' => 'Password123!',
        ]);

        $postResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_super_admin_direct_login_and_highest_authority(): void
    {
        $superAdmin = User::factory()->create([
            'account_type' => 'super_admin',
            'email' => 'ldrrmo.head.'.time().'@hinobaan.gov.ph',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $response = $this->post(route('super-admin.login.post'), [
            'login' => $superAdmin->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticatedAs($superAdmin);

        // Super Admin can access Super Admin dashboard
        $dashResponse = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));
        $dashResponse->assertStatus(200);
    }

    public function test_admin_cannot_access_super_admin_routes(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('super-admin.dashboard'));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_personnel_select_online_updates_status_and_enters_dashboard(): void
    {
        $office = Office::first() ?? Office::factory()->create();
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'B-TEST-'.time(),
            'rank_designation' => 'Responder',
        ]);

        $response = $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'online',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'available',
        ]);

        // Dashboard renders successfully
        $dashResponse = $this->actingAs($user)->get(route('personnel.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee($user->name);
    }

    public function test_personnel_select_offline_updates_status_and_enters_dashboard(): void
    {
        $office = Office::first() ?? Office::factory()->create();
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'B-TEST-OFF-'.time(),
            'rank_designation' => 'Responder',
        ]);

        $response = $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'offline',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'off_duty',
        ]);

        // Dashboard renders successfully
        $dashResponse = $this->actingAs($user)->get(route('personnel.dashboard'));
        $dashResponse->assertStatus(200);
    }

    public function test_personnel_select_offline_with_active_assignments_preserves_assignments(): void
    {
        $office = Office::first() ?? Office::factory()->create();
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
        ]);
        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'B-ASSIGN-'.time(),
            'rank_designation' => 'Lead Responder',
        ]);

        $barangay = Barangay::first();
        $category = IncidentCategory::first();
        $citizen = User::factory()->create(['account_type' => 'citizen']);

        $incident = Incident::create([
            'reference_number' => 'INC-TEST-'.time(),
            'incident_category_id' => $category?->id ?? 1,
            'barangay_id' => $barangay?->id ?? 1,
            'reported_by_id' => $citizen->id,
            'caller_name' => 'Juan Citizen',
            'caller_phone' => '09171234567',
            'status' => 'dispatched',
            'priority' => 'high',
            'description' => 'Test active assignment preservation',
            'reported_at' => now(),
            'submitted_at' => now(),
        ]);

        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'assigned_by' => $citizen->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'offline',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));
        $response->assertSessionHas('success');

        // Status is off_duty
        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'off_duty',
        ]);

        // Assignment is preserved intact
        $this->assertDatabaseHas('incident_assignments', [
            'id' => $assignment->id,
            'personnel_id' => $profile->id,
            'status' => 'active',
        ]);

        // Dashboard displays active task count
        $dashResponse = $this->actingAs($user)->get(route('personnel.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Assigned Tasks');
    }

    public function test_personnel_availability_json_api_response_for_frontend(): void
    {
        $office = Office::first() ?? Office::factory()->create();
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
        ]);
        PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'B-API-'.time(),
            'rank_designation' => 'Responder',
        ]);

        $response = $this->actingAs($user)->postJson(route('personnel.availability.update'), [
            'status' => 'online',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('personnel.dashboard'),
        ]);
    }
}
