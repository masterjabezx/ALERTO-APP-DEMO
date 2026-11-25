<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Office;
use App\Models\Role;
use App\Models\SosAlert;
use App\Models\SystemBackupRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MajorRedesignPortalTest extends TestCase
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

    public function test_welcome_gateway_displays_citizen_portal_guest_actions_and_dual_hotlines(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Citizen Portal');
        $response->assertSee('Citizen Sign In');
        $response->assertSee('Submit Emergency Report (No Login)');
        $response->assertSee('LDRRMO');

        // Operational portals must not appear in public welcome
        $response->assertDontSee('ALERTO System Portals');
        $response->assertDontSee('Super Admin &rarr;');
    }

    public function test_super_admin_portals_and_exclusive_routes_enforce_strict_authorization(): void
    {
        $superAdmin = $this->createAccount('super_admin');
        $admin = $this->createAccount('admin');

        $this->actingAs($admin)
            ->get(route('super-admin.dashboard'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($superAdmin)->get(route('super-admin.dashboard'))->assertStatus(200)->assertSee('Executive Command');
        $this->actingAs($superAdmin)->get(route('super-admin.monitoring'))->assertStatus(200)->assertSee('System-Wide Live Monitoring');
        $this->actingAs($superAdmin)->get(route('super-admin.admins.index'))->assertStatus(200)->assertSee('DIAL DAPH Admin Accounts');
        $this->actingAs($superAdmin)->get(route('super-admin.routing.index'))->assertStatus(200)->assertSee('Automated Routing Rules');
        $this->actingAs($superAdmin)->get(route('super-admin.backups.index'))->assertStatus(200)->assertSee('System Backup');
    }

    public function test_super_admin_can_create_admin_invite_link_routing_rule_and_backup(): void
    {
        Storage::fake('local');
        $superAdmin = $this->createAccount('super_admin');
        $category = IncidentCategory::firstOrFail();
        $office = Office::create([
            'name' => 'Test Rescue Unit '.Str::random(4),
            'code' => 'TRU-'.strtoupper(Str::random(4)),
            'department_type' => 'disaster',
            'contact_phone' => '0917'.random_int(1000000, 9999999),
            'contact_email' => 'tru'.Str::random(4).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);

        // 1. Direct admin creation
        $email = 'cmd.'.Str::random(5).'@hinobaan.gov.ph';
        $this->actingAs($superAdmin)->post(route('super-admin.admins.store'), [
            'name' => 'DIAL DAPH Duty Officer',
            'email' => $email,
            'phone_number' => '0918'.random_int(1000000, 9999999),
            'password' => 'CommandCenter#2026',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => strtolower($email),
            'account_type' => 'admin',
        ]);

        // 2. One-time setup invitation link & acceptance
        $inviteEmail = 'invite.'.Str::random(5).'@hinobaan.gov.ph';
        $inviteRes = $this->actingAs($superAdmin)->post(route('super-admin.admins.invite'), [
            'name' => 'Invited Operator',
            'email' => $inviteEmail,
            'expires_hours' => 24,
        ]);
        $inviteRes->assertSessionHasNoErrors();
        $setupUrl = session('generated_setup_url');
        $this->assertNotEmpty($setupUrl);
        $token = basename((string) parse_url($setupUrl, PHP_URL_PATH));

        auth()->logout();
        $this->get(route('admin.setup.show', $token))->assertStatus(200)->assertSee('Invited Operator');
        $this->post(route('admin.setup.store', $token), [
            'password' => 'InvitedOperator#2026',
            'password_confirmation' => 'InvitedOperator#2026',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => strtolower($inviteEmail),
            'account_type' => 'admin',
        ]);

        // 3. Routing rule & backup creation as Super Admin
        $this->actingAs($superAdmin)->post(route('super-admin.routing.incidents.store'), [
            'category_id' => $category->id,
            'target_office_id' => $office->id,
            'required_responders_count' => 1,
            'escalation_minutes' => 10,
            'auto_dispatch' => 1,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('incident_routing_rules', [
            'category_id' => $category->id,
            'target_office_id' => $office->id,
        ]);

        $this->actingAs($superAdmin)->post(route('super-admin.backups.store'), [
            'notes' => 'Automated Verification Snapshot',
        ])->assertSessionHasNoErrors();

        $backup = SystemBackupRecord::latest('id')->first();
        $this->assertNotNull($backup);
        Storage::disk('local')->assertExists($backup->file_path);
    }

    public function test_guest_simplified_report_and_sos_workflows_end_to_end(): void
    {
        $admin = $this->createAccount('admin');
        $category = IncidentCategory::firstOrFail();
        $barangay = Barangay::firstOrFail();

        // Guest simplified report submission (no login, no category)
        $this->get(route('guest.report'))->assertStatus(200);
        $reportRes = $this->post(route('guest.report.submit'), [
            'caller_name' => 'Maria Guest Caller',
            'caller_phone' => '09175551234',
            'latitude' => 9.5897,
            'longitude' => 122.4675,
            'location_source' => 'gps_browser',
        ]);
        $reportRes->assertSessionHasNoErrors();

        $incident = Incident::where('caller_phone', '09175551234')->latest('id')->first();
        $this->assertNotNull($incident);
        $this->assertTrue((bool) $incident->is_guest_report);
        $this->assertEquals('new_report', $incident->workflow_stage);
        $this->assertNull($incident->category_id);

        $reportRes->assertRedirect(route('guest.report.confirmation', $incident->reference_number));
        $this->get(route('guest.report.confirmation', $incident->reference_number))->assertStatus(200)->assertSee($incident->reference_number);

        // Admin views incoming queue, calls citizen, records outcome, and assesses incident
        $this->actingAs($admin)->get(route('admin.incoming.index'))->assertStatus(200)->assertSee($incident->reference_number);
        $this->actingAs($admin)->get(route('admin.incidents.show', $incident->reference_number))->assertStatus(200);

        $this->actingAs($admin)->post(route('admin.incidents.call-attempt', $incident->reference_number), [
            'phone_number' => $incident->caller_phone,
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.incidents.call-outcome', $incident->reference_number), [
            'call_outcome' => 'answered',
            'verification_notes' => 'Caller confirmed structure fire near market.',
            'location_confirmed' => 1,
            'mark_number_verified' => 1,
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.incidents.assess-deploy', $incident->reference_number), [
            'category_id' => $category->id,
            'priority' => 'critical',
            'incident_description' => 'Confirmed two-story house fire requiring immediate fire suppression.',
            'verified_barangay_id' => $barangay->id,
            'location_confirmed' => 1,
            'category_confirmed' => 1,
            'mark_number_verified' => 1,
        ])->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertEquals($category->id, $incident->category_id);
        $this->assertEquals('critical', $incident->priority);
        $this->assertEquals('completed', $incident->assessment_status);

        // Guest SOS flow & Admin SOS handling
        auth()->logout();
        $sosRes = $this->post(route('guest.sos.trigger'), [
            'sender_name' => 'Distressed Guest',
            'sender_phone' => '09178889999',
            'latitude' => 9.5910,
            'longitude' => 122.4650,
            'location_status' => 'live',
        ]);
        $sosRes->assertSessionHasNoErrors();

        $sos = SosAlert::where('contact_number', '09178889999')->latest('id')->first();
        $this->assertNotNull($sos);
        $this->assertEquals('guest_citizen', $sos->source_type);
        $this->assertEquals('guest', $sos->sender_type);
        $this->get(route('guest.sos.confirmation', $sos->reference_number))->assertStatus(200)->assertSee($sos->reference_number);

        $this->actingAs($admin)->get(route('admin.sos.index'))->assertStatus(200)->assertSee($sos->reference_number);
        $this->actingAs($admin)->get(route('admin.sos.show', $sos->id))->assertStatus(200)->assertSee($sos->reference_number);
        $this->actingAs($admin)->post(route('admin.sos.acknowledge', $sos->id))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.sos.convert', $sos->id))->assertSessionHasNoErrors();

        $sos->refresh();
        $this->assertNotNull($sos->converted_incident_id);

        // Dispatch console & citizen accounts directory
        $this->actingAs($admin)->get(route('admin.dispatch.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.citizens.index'))->assertStatus(200);
    }
}
