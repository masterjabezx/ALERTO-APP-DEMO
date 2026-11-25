<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Notification;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCommandCenterTest extends TestCase
{
    use DatabaseTransactions;

    private function createUser(string $type): User
    {
        return User::factory()->create([
            'account_type' => $type,
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
    }

    private function createOfficeUserWithAgency(): User
    {
        $office = Office::create([
            'name' => 'MDRRMO Test Desk '.Str::random(4),
            'code' => 'EOC-'.strtoupper(Str::random(4)),
            'department_type' => 'disaster',
            'contact_phone' => '0917'.rand(1000000, 9999999),
            'contact_email' => 'eoc'.Str::random(5).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);

        $user = $this->createUser('office');
        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        return $user;
    }

    private function createIncident(User $reporter, string $status = 'pending_review', string $priority = 'critical'): Incident
    {
        $category = IncidentCategory::firstOrFail();
        $barangay = Barangay::firstOrFail();

        $incident = Incident::create([
            'reference_number' => 'INC-EOC-'.strtoupper(Str::random(6)),
            'reporter_id' => $reporter->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'priority' => $priority,
            'status' => $status,
            'description' => 'Step 17 Command Center Telemetry Incident',
            'caller_name' => $reporter->name,
            'caller_phone' => '09171234567',
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 9.5985,
            'longitude' => 122.4701,
            'location_source' => 'gps_device',
            'accuracy' => 10.0,
        ]);

        return $incident;
    }

    public function test_admin_dashboard_renders_eoc_command_center_with_real_database_telemetry(): void
    {
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen, 'pending_review', 'critical');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('ALERTO EOC');
        $response->assertSee('Command &amp; Coordination Center', false);
        $response->assertSee('eoc-globe-canvas', false);
        $response->assertSee('eoc-ambient-particles', false);
        $response->assertSee($incident->reference_number);
    }

    public function test_dashboard_telemetry_json_endpoint_returns_real_metrics_and_supports_filters(): void
    {
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen, 'pending_review', 'high');

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.telemetry', [
            'category_id' => $incident->category_id,
            'barangay_id' => $incident->barangay_id,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'telemetry' => [
                'stats' => ['total', 'pending_review', 'accepted', 'dispatched', 'active_incidents', 'critical_count', 'resolved'],
                'presence' => ['personnel_online', 'offices_online', 'citizens_online', 'admins_online'],
                'charts' => ['by_category', 'by_barangay', 'by_status', 'monthly_trends', 'response_performance', 'office_activity', 'personnel_activity'],
                'live_activity',
            ],
            'shell' => ['pending_incidents', 'critical_active', 'fb_unread_total', 'internal_unread', 'unread_notifications'],
        ]);
    }

    public function test_live_user_monitor_displays_role_presence_without_leaking_citizen_gps(): void
    {
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $officeUser = $this->createOfficeUserWithAgency();

        UserPresence::create([
            'user_id' => $citizen->id,
            'status' => 'online',
            'current_device' => 'Android Mobile',
            'ip_address' => '127.0.0.1',
            'last_heartbeat_at' => now(),
        ]);

        UserPresence::create([
            'user_id' => $officeUser->id,
            'status' => 'online',
            'current_device' => 'EOC Workstation',
            'ip_address' => '127.0.0.1',
            'last_heartbeat_at' => now(),
        ]);

        $pageResponse = $this->actingAs($admin)->get(route('admin.monitor.users'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Live User Monitor');
        $pageResponse->assertSee($citizen->name);
        $pageResponse->assertSee($officeUser->name);

        $jsonResponse = $this->actingAs($admin)->getJson(route('admin.monitor.users.data', ['account_type' => 'citizen']));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonFragment(['id' => $citizen->id, 'account_type' => 'citizen']);

        // Citizen records must never expose latitude or longitude keys
        $payload = $jsonResponse->json();
        foreach ($payload['citizen_users'] ?? [] as $citizenRow) {
            $this->assertArrayNotHasKey('latitude', $citizenRow);
            $this->assertArrayNotHasKey('longitude', $citizenRow);
        }
    }

    public function test_admin_notification_center_renders_and_marks_notifications_read(): void
    {
        $admin = $this->createUser('admin');

        $n1 = Notification::create([
            'user_id' => $admin->id,
            'title' => 'Critical Emergency Alert',
            'body' => 'Fire emergency reported in Barangay Poblacion.',
            'type' => 'incident_alert',
            'action_url' => route('admin.incidents.index'),
            'is_read' => false,
            'created_at' => now(),
        ]);

        $n2 = Notification::create([
            'user_id' => $admin->id,
            'title' => 'Responder Check-In',
            'body' => 'QRF unit arrived on scene.',
            'type' => 'responder_status',
            'action_url' => route('admin.map'),
            'is_read' => false,
            'created_at' => now(),
        ]);

        $indexResp = $this->actingAs($admin)->get(route('admin.notifications.index'));
        $indexResp->assertOk();
        $indexResp->assertSee('Admin Notification Center');
        $indexResp->assertSee('Critical Emergency Alert');

        $singleResp = $this->actingAs($admin)->postJson(route('admin.notifications.read', $n1->id));
        $singleResp->assertOk();
        $this->assertTrue((bool) $n1->fresh()->is_read);
        $this->assertFalse((bool) $n2->fresh()->is_read);

        $allResp = $this->actingAs($admin)->postJson(route('admin.notifications.read-all'));
        $allResp->assertOk();
        $this->assertTrue((bool) $n2->fresh()->is_read);
    }

    public function test_reports_analytics_page_and_csv_export_work_with_real_incidents(): void
    {
        $admin = $this->createUser('admin');
        $citizen = $this->createUser('citizen');
        $incident = $this->createIncident($citizen, 'accepted', 'high');

        $pageResp = $this->actingAs($admin)->get(route('admin.reports.index', ['status' => 'accepted']));
        $pageResp->assertOk();
        $pageResp->assertSee('Reports &amp; Analytics', false);
        $pageResp->assertSee($incident->reference_number);

        $exportResp = $this->actingAs($admin)->get(route('admin.reports.export', ['status' => 'accepted']));
        $exportResp->assertOk();
        $csv = $exportResp->streamedContent();
        $this->assertStringContainsString('Reference Number', $csv);
        $this->assertStringContainsString($incident->reference_number, $csv);
    }

    public function test_activity_logs_page_renders_audit_trails(): void
    {
        $admin = $this->createUser('admin');

        AuditLog::create([
            'user_id' => $admin->id,
            'event_type' => 'eoc:inspection_probe',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'new_values' => ['note' => 'Verified'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit.index', ['search' => 'eoc:inspection_probe']));
        $response->assertOk();
        $response->assertSee('Activity &amp; Audit Logs', false);
        $response->assertSee('eoc:inspection_probe');
    }

    public function test_admin_can_view_and_update_eoc_settings_and_system_settings(): void
    {
        $admin = $this->createUser('admin');

        $eocPage = $this->actingAs($admin)->get(route('admin.eoc-settings'));
        $eocPage->assertOk()->assertSee('Command Center Settings');

        $eocSave = $this->actingAs($admin)->post(route('admin.eoc-settings.update'), [
            'hotline_title' => 'DIAL DAPH COMMAND CENTER',
            'hotline' => '0999-123-4567',
            'facebook_url' => 'https://www.facebook.com/HinobaanLDRRMO',
            'lat_min' => 9.4500,
            'lat_max' => 9.7500,
            'lng_min' => 122.3500,
            'lng_max' => 122.6500,
        ]);
        $eocSave->assertRedirect();
        $this->assertSame('0999-123-4567', SystemSetting::get('dial_daph_command_center_hotline'));

        $sysPage = $this->actingAs($admin)->get(route('admin.settings.index'));
        $sysPage->assertOk()->assertSee('System Settings');

        $sysSave = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'max_upload_size_mb' => 45,
            'max_upload_count' => 8,
        ]);
        $sysSave->assertRedirect();
        $this->assertSame(45, (int) SystemSetting::get('max_upload_size_mb'));
        $this->assertSame(8, (int) SystemSetting::get('max_upload_count'));
    }

    public function test_non_admin_accounts_are_blocked_from_admin_command_center_routes(): void
    {
        $citizen = $this->createUser('citizen');
        $personnel = $this->createUser('personnel');
        $office = $this->createOfficeUserWithAgency();

        foreach ([$citizen, $personnel, $office] as $actor) {
            $this->actingAs($actor)->getJson(route('admin.dashboard.telemetry'))->assertForbidden();
            $this->actingAs($actor)->getJson(route('admin.monitor.users.data'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.reports.index'))->assertRedirect();
            $this->actingAs($actor)->get(route('admin.audit.index'))->assertRedirect();
            $this->actingAs($actor)->post(route('admin.eoc-settings.update'), [
                'hotline_title' => 'Hacked',
                'hotline' => '0000',
                'facebook_url' => 'https://example.com',
                'lat_min' => 9.4,
                'lat_max' => 9.7,
                'lng_min' => 122.3,
                'lng_max' => 122.6,
            ])->assertRedirect();
            $this->actingAs($actor)->post(route('admin.settings.update'), [
                'max_upload_size_mb' => 10,
                'max_upload_count' => 2,
            ])->assertRedirect();
        }
    }

    public function test_all_other_existing_admin_subpages_render_inside_unified_eoc_shell(): void
    {
        $admin = $this->createUser('admin');
        $office = Office::first();
        if (! $office) {
            $office = Office::create([
                'name' => 'Hinoba-an LDRRMO',
                'code' => 'LDRRMO',
                'department_type' => 'disaster',
                'contact_phone' => '09170001122',
                'contact_email' => 'ldrrmo@hinobaan.gov.ph',
                'is_active' => true,
            ]);
        }

        if (! PersonnelProfile::where('office_id', $office->id)->exists()) {
            $pUser = $this->createUser('personnel');
            PersonnelProfile::create([
                'user_id' => $pUser->id,
                'office_id' => $office->id,
                'badge_number' => 'B-999',
                'rank_designation' => 'Responder',
                'specialization' => 'Rescue',
                'is_active_responder' => true,
            ]);
        }

        $routes = [
            'admin.incidents.index',
            'admin.map',
            'admin.personnel.index',
            'admin.offices.index',
            'admin.messages.index',
            'admin.communications.fb.inbox',
            'admin.communications.fb.settings',
            'admin.media.index',
            'admin.incidents.evidence',
        ];

        foreach ($routes as $routeName) {
            $resp = $this->actingAs($admin)->get(route($routeName));
            $resp->assertOk();
            $resp->assertSee('eoc-admin-shell', false);
        }
    }
}
