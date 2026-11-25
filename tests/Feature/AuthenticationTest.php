<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\CitizenProfile;
use App\Models\Office;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication system integration tests.
 *
 * Uses DatabaseTransactions against the isolated alerto_app_testing database
 * so changes are rolled back after each test.
 */
class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Create a minimal active user of the given account_type. */
    private function createUser(string $accountType, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => $accountType,
            'is_active' => true,
            'password' => Hash::make('Password123!'),
        ], $overrides));
    }

    /** Create an active citizen user (no CitizenProfile needed for auth tests). */
    private function makeCitizen(array $overrides = []): User
    {
        return $this->createUser('citizen', $overrides);
    }

    private function makePersonnel(array $overrides = []): User
    {
        return $this->createUser('personnel', $overrides);
    }

    private function makeOffice(array $overrides = []): User
    {
        return $this->createUser('office', $overrides);
    }

    private function makeAdmin(array $overrides = []): User
    {
        return $this->createUser('admin', $overrides);
    }

    // -------------------------------------------------------------------------
    // Landing / Gateway
    // -------------------------------------------------------------------------

    public function test_gateway_page_loads(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('ALERTO');
    }

    public function test_authenticated_citizen_redirected_from_gateway_to_dashboard(): void
    {
        $user = $this->makeCitizen();
        $response = $this->actingAs($user)->get(route('home'));
        $response->assertRedirect(route('citizen.dashboard'));
    }

    public function test_authenticated_personnel_redirected_from_gateway_to_availability(): void
    {
        $user = $this->makePersonnel();
        $response = $this->actingAs($user)->get(route('home'));
        $response->assertRedirect(route('personnel.availability'));
    }

    public function test_authenticated_office_redirected_from_gateway_to_dashboard(): void
    {
        $user = $this->makeOffice();
        $response = $this->actingAs($user)->get(route('home'));
        $response->assertRedirect(route('office.dashboard'));
    }

    public function test_authenticated_admin_redirected_from_gateway_to_dashboard(): void
    {
        $user = $this->makeAdmin();
        $response = $this->actingAs($user)->get(route('home'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    // -------------------------------------------------------------------------
    // Login Pages Load
    // -------------------------------------------------------------------------

    public function test_citizen_login_page_loads(): void
    {
        $this->get(route('citizen.login'))->assertStatus(200)->assertSee('Citizen');
    }

    public function test_personnel_login_page_loads(): void
    {
        $this->get(route('personnel.login'))->assertStatus(200)->assertSee('Personnel');
    }

    public function test_office_login_page_loads(): void
    {
        $this->get(route('office.login'))->assertStatus(200)->assertSee('Office');
    }

    public function test_admin_login_page_loads(): void
    {
        $this->get(route('admin.login'))->assertStatus(200)->assertSee('Admin');
    }

    // -------------------------------------------------------------------------
    // Successful Logins
    // -------------------------------------------------------------------------

    public function test_citizen_can_login_with_email(): void
    {
        $user = $this->makeCitizen(['email' => 'citizen.test@alerto.local']);

        $response = $this->post(route('citizen.login.post'), [
            'login' => 'citizen.test@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_citizen_can_login_with_phone_number(): void
    {
        $user = $this->makeCitizen(['phone_number' => '09171234567']);

        $response = $this->post(route('citizen.login.post'), [
            'login' => '09171234567',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_personnel_login_redirects_to_availability(): void
    {
        $user = $this->makePersonnel(['email' => 'personnel.test@alerto.local']);

        $response = $this->post(route('personnel.login.post'), [
            'login' => 'personnel.test@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('personnel.availability'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_office_login_redirects_to_dashboard(): void
    {
        $user = $this->makeOffice(['email' => 'office.test@alerto.local']);

        $response = $this->post(route('office.login.post'), [
            'login' => 'office.test@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('office.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_redirects_to_dashboard(): void
    {
        $user = $this->makeAdmin(['email' => 'admin.test@alerto.local']);

        $response = $this->post(route('admin.login.post'), [
            'login' => 'admin.test@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    // -------------------------------------------------------------------------
    // Wrong Password / Non-existent User
    // -------------------------------------------------------------------------

    public function test_citizen_login_fails_with_wrong_password(): void
    {
        $this->makeCitizen(['email' => 'citizen.wrong@alerto.local']);

        $response = $this->post(route('citizen.login.post'), [
            'login' => 'citizen.wrong@alerto.local',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_login_fails_for_nonexistent_user(): void
    {
        $response = $this->post(route('citizen.login.post'), [
            'login' => 'nobody@nowhere.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // Cross-Portal Login Prevention
    // -------------------------------------------------------------------------

    public function test_citizen_cannot_login_through_admin_portal(): void
    {
        $this->makeCitizen(['email' => 'citizen.crossportal@alerto.local']);

        $response = $this->post(route('admin.login.post'), [
            'login' => 'citizen.crossportal@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_admin_cannot_login_through_citizen_portal(): void
    {
        $this->makeAdmin(['email' => 'admin.crossportal@alerto.local']);

        $response = $this->post(route('citizen.login.post'), [
            'login' => 'admin.crossportal@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_personnel_cannot_login_through_office_portal(): void
    {
        $this->makePersonnel(['email' => 'personnel.crossportal@alerto.local']);

        $response = $this->post(route('office.login.post'), [
            'login' => 'personnel.crossportal@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // Deactivated Account
    // -------------------------------------------------------------------------

    public function test_deactivated_citizen_cannot_login(): void
    {
        $this->createUser('citizen', [
            'email' => 'deactivated.citizen@alerto.local',
            'is_active' => false,
        ]);

        $response = $this->post(route('citizen.login.post'), [
            'login' => 'deactivated.citizen@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_deactivated_admin_cannot_login(): void
    {
        $this->createUser('admin', [
            'email' => 'deactivated.admin@alerto.local',
            'is_active' => false,
        ]);

        $response = $this->post(route('admin.login.post'), [
            'login' => 'deactivated.admin@alerto.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // Middleware — Unauthorized Direct Access
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_redirected_from_citizen_dashboard(): void
    {
        $this->get(route('citizen.dashboard'))->assertRedirect(route('home'));
    }

    public function test_unauthenticated_user_redirected_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('home'));
    }

    public function test_unauthenticated_user_redirected_from_personnel_availability(): void
    {
        $this->get(route('personnel.availability'))->assertRedirect(route('home'));
    }

    // -------------------------------------------------------------------------
    // Role Escalation Prevention
    // -------------------------------------------------------------------------

    public function test_citizen_cannot_access_admin_dashboard(): void
    {
        $user = $this->makeCitizen();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        // Should redirect away (not 200)
        $response->assertRedirect();
    }

    public function test_citizen_cannot_access_personnel_dashboard(): void
    {
        $user = $this->makeCitizen();

        $response = $this->actingAs($user)->get(route('personnel.dashboard'));
        $response->assertRedirect();
    }

    public function test_admin_cannot_access_citizen_dashboard(): void
    {
        $user = $this->makeAdmin();

        $response = $this->actingAs($user)->get(route('citizen.dashboard'));
        $response->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // Personnel Availability
    // -------------------------------------------------------------------------

    public function test_personnel_can_set_status_to_online(): void
    {
        $user = $this->makePersonnel();

        $response = $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'online',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));

        $user->refresh();
        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $user->personnelProfile->id,
            'operational_status' => 'available',
        ]);
    }

    public function test_personnel_can_set_status_to_offline(): void
    {
        $user = $this->makePersonnel();

        $response = $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'offline',
        ]);

        $response->assertRedirect(route('personnel.dashboard'));

        $user->refresh();
        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $user->personnelProfile->id,
            'operational_status' => 'off_duty',
        ]);
    }

    public function test_personnel_availability_rejects_invalid_status(): void
    {
        $user = $this->makePersonnel();

        $response = $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'invisible', // invalid
        ]);

        $response->assertSessionHasErrors('status');
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_logout_signs_out_user_and_redirects_to_home(): void
    {
        $user = $this->makeCitizen();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_logout_sets_personnel_availability_to_offline(): void
    {
        $user = $this->makePersonnel();
        $office = Office::first();
        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office?->id ?? 1,
            'badge_number' => 'B-TEST-'.$user->id,
            'rank_designation' => 'Medic',
        ]);

        PersonnelAvailability::updateOrCreate(
            ['personnel_id' => $profile->id],
            [
                'operational_status' => 'available',
                'status_reason' => 'Online',
                'status_updated_at' => now(),
            ]
        );

        $this->actingAs($user)->post(route('logout'));

        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'off_duty',
        ]);
    }

    // -------------------------------------------------------------------------
    // Citizen Registration
    // -------------------------------------------------------------------------

    public function test_citizen_registration_page_loads(): void
    {
        $this->get(route('citizen.register'))->assertStatus(200);
    }

    public function test_citizen_can_register_with_valid_data(): void
    {
        $barangay = Barangay::first();

        $response = $this->post(route('citizen.register.post'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz.test@alerto.local',
            'phone_number' => '09170000099',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'barangay_id' => $barangay->id,
            'street_address' => '123 Test Street',
            'latitude' => '14.5995',
            'longitude' => '120.9842',
        ]);

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'juan.delacruz.test@alerto.local',
            'account_type' => 'citizen',
        ]);
    }

    public function test_citizen_registration_fails_without_latitude(): void
    {
        $barangay = Barangay::first();

        $response = $this->post(route('citizen.register.post'), [
            'first_name' => 'Juan',
            'last_name' => 'Test',
            'email' => 'nolat.test@alerto.local',
            'phone_number' => '09170000098',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'barangay_id' => $barangay->id,
            // latitude missing
            'longitude' => '120.9842',
            'street_address' => '123 Test',
        ]);

        $response->assertSessionHasErrors('latitude');
        $this->assertGuest();
    }

    public function test_citizen_registration_fails_with_duplicate_email(): void
    {
        $barangay = Barangay::first();
        $this->makeCitizen(['email' => 'duplicate.email@alerto.local']);

        $response = $this->post(route('citizen.register.post'), [
            'first_name' => 'Juan',
            'last_name' => 'Test',
            'email' => 'duplicate.email@alerto.local',
            'phone_number' => '09170000097',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'barangay_id' => $barangay->id,
            'latitude' => '14.5995',
            'longitude' => '120.9842',
            'street_address' => '123 Test',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_citizen_registration_fails_with_duplicate_phone(): void
    {
        $barangay = Barangay::first();
        $this->makeCitizen(['phone_number' => '09170000096']);

        $response = $this->post(route('citizen.register.post'), [
            'first_name' => 'Juan',
            'last_name' => 'Test',
            'email' => 'unique.phone.test@alerto.local',
            'phone_number' => '09170000096',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'barangay_id' => $barangay->id,
            'latitude' => '14.5995',
            'longitude' => '120.9842',
            'street_address' => '123 Test',
        ]);

        $response->assertSessionHasErrors('phone_number');
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // Rate Limiting
    // -------------------------------------------------------------------------

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $email = 'ratelimit.test@alerto.local';
        $this->makeCitizen(['email' => $email]);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('citizen.login.post'), [
                'login' => $email,
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be blocked and redirected with throttle error
        $response = $this->post(route('citizen.login.post'), [
            'login' => $email,
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('login');
    }

    // -------------------------------------------------------------------------
    // Form Validation
    // -------------------------------------------------------------------------

    public function test_login_requires_login_field(): void
    {
        $response = $this->post(route('citizen.login.post'), [
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('login');
    }

    public function test_login_requires_password_field(): void
    {
        $response = $this->post(route('citizen.login.post'), [
            'login' => 'test@alerto.local',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
