<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UiDesignSystemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_citizen_dashboard_view_renders_successfully(): void
    {
        $citizen = User::factory()->create([
            'account_type' => 'citizen',
            'is_active' => true,
        ]);

        $response = $this->actingAs($citizen)->get(route('citizen.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ALERTO');
        $response->assertSee('DIAL DAPH COMMAND CENTER');
        $response->assertSee('Submit Incident');
        $response->assertSee('Incident Record');
        $response->assertSee('Contact Numbers');
    }

    public function test_personnel_dashboard_view_renders_successfully(): void
    {
        $personnel = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
        ]);

        $response = $this->actingAs($personnel)->get(route('personnel.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ALERTO');
        $response->assertSee('Tasks');
        $response->assertSee('Active Incidents');
        $response->assertSee('Live Incident Map');
        $response->assertSee('Records & Incidents');
        $response->assertSee('Messages');
    }

    public function test_office_dashboard_view_renders_successfully(): void
    {
        $officeAgency = Office::factory()->create(['is_active' => true]);
        $officeUser = User::factory()->create([
            'account_type' => 'office',
            'is_active' => true,
        ]);
        $officeUser->offices()->attach($officeAgency->id, [
            'office_level' => 'office_staff',
            'can_review_incidents' => false,
            'can_dispatch_units' => false,
        ]);

        $response = $this->actingAs($officeUser)->get(route('office.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ALERTO');
        $response->assertSee('Office Dashboard');
        $response->assertSee('Incident Monitoring');
        $response->assertSee('Office Incident Map');
        $response->assertSee('Records & Reports');
        $response->assertSee('Messages');
    }

    public function test_admin_dashboard_view_renders_successfully(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('DIAL DAPH');
        $response->assertSee('COMMAND CENTER');
        $response->assertSee('Dashboard');
        $response->assertSee('Incoming Reports');
        $response->assertSee('SOS Alerts');
        $response->assertSee('Incidents &amp; Triage', false);
        $response->assertSee('Live Operations Map');
        $response->assertSee('Dispatch Console');
        $response->assertSee('Personnel Management');
        $response->assertSee('Office Management');
        $response->assertSee('Citizen Accounts');
        $response->assertSee('Live User Monitor');
        $response->assertSee('Internal Messenger');
        $response->assertSee('Facebook Messenger');
        $response->assertSee('Command Center Settings');
        $response->assertSee('Reports &amp; Analytics', false);
    }
}
