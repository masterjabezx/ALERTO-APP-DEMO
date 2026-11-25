<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelProfile;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PwaOfflineSyncTest extends TestCase
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

    private function createOfficeUser(): User
    {
        $office = Office::create([
            'name' => 'Hinoba-an EOC Desk '.Str::random(4),
            'code' => 'DESK-'.strtoupper(Str::random(4)),
            'department_type' => 'disaster',
            'contact_phone' => '0917'.rand(1000000, 9999999),
            'contact_email' => 'desk'.Str::random(5).'@hinobaan.gov.ph',
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

    private function createPersonnelWithAssignment(): array
    {
        $office = Office::first() ?? Office::create([
            'name' => 'MDRRMO Rescue Unit',
            'code' => 'MDRRMO-QRU',
            'department_type' => 'disaster',
            'contact_phone' => '09171234567',
            'is_active' => true,
        ]);

        $user = $this->createUser('personnel');
        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'TEST-OP-'.rand(100, 999),
            'rank_designation' => 'Rescue Officer',
            'specialization' => 'First Aid',
            'is_team_leader' => false,
        ]);

        PersonnelAvailability::create([
            'personnel_id' => $profile->id,
            'operational_status' => 'dispatched',
            'status_updated_at' => now(),
        ]);

        $category = IncidentCategory::first() ?? IncidentCategory::create([
            'name' => 'Medical Emergency',
            'slug' => 'medical-'.Str::random(5),
            'default_priority' => 'critical',
            'is_active' => true,
        ]);

        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-HNO-'.rand(1000, 9999),
            'reporter_id' => $user->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'critical',
            'status' => 'dispatched',
            'description' => 'Test emergency incident for responder assignment',
            'caller_name' => 'Test Citizen',
            'caller_phone' => '09171112233',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
        ]);

        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'assigned_by' => $user->id,
            'status' => 'active',
            'assigned_at' => now(),
            'notes' => 'Priority response dispatched',
        ]);

        return [$user, $profile, $incident, $assignment];
    }

    /**
     * 1. Test PWA manifest is present and valid.
     */
    public function test_pwa_manifest_is_valid_json_with_emergency_configuration(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertSame('ALERTO', $json['short_name']);
        $this->assertSame('standalone', $json['display']);
        $this->assertSame('#0B1726', $json['theme_color']);
        $this->assertSame('#0B1726', $json['background_color']);
        $this->assertNotEmpty($json['icons']);

        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        foreach ($json['shortcuts'] ?? [] as $shortcut) {
            foreach ($shortcut['icons'] ?? [] as $shortcutIcon) {
                $this->assertFileExists(public_path(ltrim($shortcutIcon['src'], '/')));
            }
        }
    }

    /**
     * 2. Test service worker file exists and contains security safety rules.
     */
    public function test_service_worker_file_exists_and_contains_safety_rules(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('alerto-pwa-', $content);
        $this->assertStringContainsString('/images/branding/alerto-app-icon-192.png', $content);
        // Sensitive routes must never be cached in Service Worker
        $this->assertStringContainsString('/login', $content);
        $this->assertStringContainsString('/logout', $content);
        $this->assertStringContainsString('/offline', $content);

        // Verify all static branding assets cached by sw.js exist on disk
        if (preg_match('/const\s+STATIC_ASSETS\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            preg_match_all("/'([^']+)'/", $matches[1], $paths);
            foreach ($paths[1] as $assetPath) {
                if ($assetPath === '/offline') {
                    continue;
                }
                $this->assertFileExists(public_path(ltrim($assetPath, '/')));
            }
        }
    }

    /**
     * 3. Test offline fallback page renders with DIAL DAPH hotline.
     */
    public function test_offline_fallback_page_renders_with_dial_daph_hotline(): void
    {
        $response = $this->get('/offline');

        $response->assertStatus(200);
        $response->assertSee('Connection Lost');
        $response->assertSee('Local Emergency Mode');
        $response->assertSee('tel:');
    }

    /**
     * 4. Test PWA ping returns connectivity status and auth context.
     */
    public function test_pwa_ping_returns_connectivity_status_and_auth_context(): void
    {
        // Guest ping
        $guestRes = $this->getJson('/pwa/ping');
        $guestRes->assertStatus(200);
        $guestRes->assertJson([
            'status' => 'online',
            'authenticated' => false,
        ]);

        // Authenticated citizen ping
        $citizen = $this->createUser('citizen');
        $authRes = $this->actingAs($citizen)->getJson('/pwa/ping');
        $authRes->assertStatus(200);
        $authRes->assertJson([
            'status' => 'online',
            'authenticated' => true,
            'user' => [
                'id' => $citizen->id,
                'name' => $citizen->name,
                'account_type' => 'citizen',
            ],
        ]);
    }

    /**
     * 5. Test Citizen can sync offline incident draft.
     */
    public function test_citizen_can_sync_offline_incident_draft(): void
    {
        Storage::fake('private');
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first() ?? IncidentCategory::create([
            'name' => 'Fire Emergency',
            'slug' => 'fire-'.Str::random(4),
            'default_priority' => 'critical',
            'is_active' => true,
        ]);
        $barangay = Barangay::first();
        $idempotencyKey = 'draft_idemp_'.Str::random(16);
        $clientUuid = Str::uuid()->toString();

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'client_uuid' => $clientUuid,
            'category_id' => $category->id,
            'caller_phone' => '09178887766',
            'caller_name' => $citizen->name,
            'barangay_id' => $barangay->id,
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'accuracy' => 12.5,
            'location_source' => 'gps_device',
            'street_address' => 'Purok 3 Center',
            'landmark' => 'Near Chapel',
            'description' => 'Structural fire witnessed from residence',
            'attachments' => [
                UploadedFile::fake()->create('scene_photo.jpg', 50, 'image/jpeg'),
            ],
        ];

        $response = $this->actingAs($citizen)->postJson('/pwa/sync/incident', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'is_duplicate' => false,
        ]);

        $this->assertDatabaseHas('incidents', [
            'idempotency_key' => $idempotencyKey,
            'client_uuid' => $clientUuid,
            'reporter_id' => $citizen->id,
            'status' => 'pending_review',
        ]);
    }

    /**
     * 6. Test duplicate draft submission is prevented by idempotency key.
     */
    public function test_duplicate_draft_submission_prevented_by_idempotency_key(): void
    {
        $citizen = $this->createUser('citizen');
        $category = IncidentCategory::first();
        $barangay = Barangay::first();
        $idempotencyKey = 'draft_dedup_'.Str::random(16);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'category_id' => $category->id,
            'caller_phone' => '09175554433',
            'barangay_id' => $barangay->id,
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
            'description' => 'Testing duplicate prevention idempotency',
        ];

        // 1st submission -> 201 Created
        $res1 = $this->actingAs($citizen)->postJson('/pwa/sync/incident', $payload);
        $res1->assertStatus(201);
        $referenceNumber = $res1->json('reference_number');

        $initialCount = Incident::where('idempotency_key', $idempotencyKey)->count();
        $this->assertSame(1, $initialCount);

        // 2nd retry submission with exact same idempotency_key -> 200 OK (Duplicate prevented)
        $res2 = $this->actingAs($citizen)->postJson('/pwa/sync/incident', $payload);
        $res2->assertStatus(200);
        $res2->assertJson([
            'is_duplicate' => true,
            'reference_number' => $referenceNumber,
        ]);

        // Total count of incidents does not increase
        $finalCount = Incident::where('idempotency_key', $idempotencyKey)->count();
        $this->assertSame(1, $finalCount);
    }

    /**
     * 7. Test personnel can download authorized offline tasks.
     */
    public function test_personnel_can_download_authorized_offline_tasks(): void
    {
        [$personnelUser, $profile, $incident, $assignment] = $this->createPersonnelWithAssignment();

        $response = $this->actingAs($personnelUser)->getJson('/pwa/tasks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'tasks_count',
            'tasks' => [
                '*' => [
                    'assignment_id',
                    'incident_id',
                    'reference_number',
                    'category_name',
                    'priority',
                    'barangay_name',
                    'latitude',
                    'longitude',
                    'current_status',
                    'version_lock',
                ],
            ],
            'cached_at',
        ]);
        $this->assertGreaterThanOrEqual(1, $response->json('tasks_count'));
    }

    /**
     * 8. Test non-personnel accounts cannot download responder tasks.
     */
    public function test_non_personnel_cannot_access_responder_tasks(): void
    {
        $citizen = $this->createUser('citizen');

        $response = $this->actingAs($citizen)->getJson('/pwa/tasks');
        $response->assertStatus(403);
    }

    /**
     * 9. Test personnel can sync queued milestone update.
     */
    public function test_personnel_can_sync_milestone_update(): void
    {
        [$personnelUser, $profile, $incident, $assignment] = $this->createPersonnelWithAssignment();
        $idempotencyKey = 'milestone_sync_'.Str::random(16);

        $payload = [
            'assignment_id' => $assignment->id,
            'milestone' => 'acknowledged',
            'expected_version_lock' => 1,
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Acknowledged while offline and synchronized',
            'latitude' => 9.5936,
            'longitude' => 122.4542,
        ];

        $response = $this->actingAs($personnelUser)->postJson('/pwa/sync/milestone', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'new_status' => 'acknowledged',
            'version_lock' => 2,
        ]);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'acknowledged',
            'version_lock' => 2,
        ]);
    }

    /**
     * 10. Test milestone sync detects conflict on version lock mismatch.
     */
    public function test_milestone_sync_detects_conflict_on_version_lock_mismatch(): void
    {
        [$personnelUser, $profile, $incident, $assignment] = $this->createPersonnelWithAssignment();

        // Advance incident on the server to 'resolved' and version lock to 3
        $incident->update([
            'status' => 'resolved',
            'version_lock' => 3,
        ]);

        $idempotencyKey = 'conflict_test_'.Str::random(16);

        // Responder device tries to sync an old queued 'on_the_way' with stale version lock 1
        $payload = [
            'assignment_id' => $assignment->id,
            'milestone' => 'on_the_way',
            'expected_version_lock' => 1,
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Old en-route milestone from offline device',
        ];

        $response = $this->actingAs($personnelUser)->postJson('/pwa/sync/milestone', $payload);

        $response->assertStatus(409);
        $response->assertJson([
            'status' => 'conflict',
            'server_status' => 'resolved',
        ]);
    }

    /**
     * 11. Test office user can download read-only offline summary.
     */
    public function test_office_can_download_read_only_offline_summary(): void
    {
        $officeUser = $this->createOfficeUser();

        $response = $this->actingAs($officeUser)->getJson('/pwa/office');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'office_id',
            'office_name',
            'incidents',
            'cached_at',
            'notice',
        ]);
    }

    /**
     * 12. Test connection lost reporting updates user presence.
     */
    public function test_connection_lost_reporting_updates_user_presence(): void
    {
        $citizen = $this->createUser('citizen');
        UserPresence::create([
            'user_id' => $citizen->id,
            'status' => 'online',
            'last_heartbeat_at' => now(),
        ]);

        $response = $this->actingAs($citizen)->postJson('/pwa/connection-lost');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'acknowledged',
            'presence' => 'connection_lost',
        ]);

        $this->assertDatabaseHas('user_presences', [
            'user_id' => $citizen->id,
            'status' => 'connection_lost',
        ]);
    }

    /**
     * 13. Test guest cannot sync incident draft.
     */
    public function test_guest_cannot_sync_incident_draft(): void
    {
        $response = $this->postJson('/pwa/sync/incident', [
            'idempotency_key' => 'guest_draft_key',
            'category_id' => 1,
            'caller_phone' => '09170001122',
            'barangay_id' => 1,
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 14. Test personnel milestone sync is idempotent on retry.
     */
    public function test_personnel_milestone_sync_is_idempotent_on_retry(): void
    {
        [$personnelUser, $profile, $incident, $assignment] = $this->createPersonnelWithAssignment();
        $idempotencyKey = 'milestone_idemp_'.Str::random(16);

        $payload = [
            'assignment_id' => $assignment->id,
            'milestone' => 'acknowledged',
            'expected_version_lock' => 1,
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Acknowledged via PWA',
        ];

        // 1st attempt
        $res1 = $this->actingAs($personnelUser)->postJson('/pwa/sync/milestone', $payload);
        $res1->assertStatus(200);
        $this->assertSame(2, $res1->json('version_lock'));

        // 2nd retry attempt with exact same idempotency_key
        $res2 = $this->actingAs($personnelUser)->postJson('/pwa/sync/milestone', $payload);
        $res2->assertStatus(200);
        $this->assertSame(2, $res2->json('version_lock'));

        // Incident version lock remains 2 (not bumped to 3)
        $this->assertSame(2, $incident->fresh()->version_lock);
    }

    /**
     * 15. Test unassigned personnel cannot sync milestone for another responder.
     */
    public function test_unassigned_personnel_cannot_sync_milestone_for_another_responder(): void
    {
        [$personnelUser, $profile, $incident, $assignment] = $this->createPersonnelWithAssignment();

        // Create a different personnel member
        $intruderUser = $this->createUser('personnel');
        PersonnelProfile::create([
            'user_id' => $intruderUser->id,
            'office_id' => $profile->office_id,
            'badge_number' => 'DIFF-OP-'.rand(100, 999),
            'rank_designation' => 'Responder',
            'specialization' => 'First Aid',
            'is_team_leader' => false,
        ]);

        $payload = [
            'assignment_id' => $assignment->id,
            'milestone' => 'acknowledged',
            'expected_version_lock' => 1,
            'idempotency_key' => 'intruder_key_'.Str::random(16),
            'notes' => 'Attempting unauthorized milestone update',
        ];

        $response = $this->actingAs($intruderUser)->postJson('/pwa/sync/milestone', $payload);
        $response->assertStatus(403);
    }
}
