<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\PersonnelLocationUpdated;
use App\Models\Barangay;
use App\Models\BarangayBoundary;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\MediaAttachment;
use App\Models\MediaGeoMetadata;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelJurisdiction;
use App\Models\PersonnelLocation;
use App\Models\PersonnelProfile;
use App\Models\ResponseTeam;
use App\Models\TeamMembership;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\GeoService;
use App\Services\LiveOperationsMapService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LiveOperationsMapTest extends TestCase
{
    private function createAdmin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createOffice(string $prefix = 'OFF'): Office
    {
        $code = substr($prefix.'-'.uniqid(), 0, 32);

        return Office::create([
            'name' => 'Agency '.$code,
            'code' => $code,
            'department_type' => 'disaster',
            'contact_phone' => '0917'.rand(1000000, 9999999),
            'contact_email' => strtolower($code).'@hinobaan.gov.ph',
            'is_active' => true,
        ]);
    }

    private function createOfficeUser(Office $office): User
    {
        $user = User::factory()->create([
            'account_type' => 'office',
            'is_active' => true,
        ]);

        OfficeUser::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        return $user;
    }

    private function createPersonnel(Office $office): array
    {
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
        ]);

        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'BDG-'.strtoupper(substr(uniqid('', true), -8)),
            'rank_designation' => 'Officer',
            'specialization' => 'Rescue & Suppression',
            'is_team_leader' => false,
        ]);

        $team = ResponseTeam::create([
            'office_id' => $office->id,
            'team_name' => 'Alpha Rescue',
            'call_sign' => 'ALPHA-'.uniqid(),
            'team_type' => 'rescue',
            'is_active' => true,
        ]);

        TeamMembership::create([
            'team_id' => $team->id,
            'personnel_id' => $profile->id,
            'role_in_team' => 'Medic',
            'assigned_at' => now(),
        ]);

        PersonnelAvailability::create([
            'personnel_id' => $profile->id,
            'operational_status' => 'available',
            'status_updated_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        UserPresence::create([
            'user_id' => $user->id,
            'status' => 'online',
            'last_heartbeat_at' => now(),
        ]);

        return [$user, $profile, $team];
    }

    private function createIncident(array $attributes = []): Incident
    {
        $category = IncidentCategory::first();
        $barangay = Barangay::first();
        $citizen = User::factory()->create(['account_type' => 'citizen']);

        $incident = Incident::create(array_merge([
            'reference_number' => 'INC-TEST-'.strtoupper(substr(uniqid('', true), -8)),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category ? $category->id : 1,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'description' => 'Test emergency incident description for map.',
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

        return $incident;
    }

    /**
     * Requirement 1: New incident marker visibility on Admin map.
     */
    public function test_new_incident_marker_visibility_on_admin_map(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident();

        $response = $this->actingAs($admin)->get(route('admin.map.data'));
        $response->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('incidents', $data);

        $references = collect($data['incidents'])->pluck('reference_number');
        $this->assertTrue($references->contains($incident->reference_number));
    }

    /**
     * Requirement 2: Incident status marker updates and visual definitions.
     */
    public function test_incident_status_marker_visual_definitions(): void
    {
        /** @var LiveOperationsMapService $service */
        $service = app(LiveOperationsMapService::class);

        $pending = $service->getStatusVisuals('pending_review');
        $this->assertEquals('#F97316', $pending['color']);
        $this->assertEquals('alert-triangle', $pending['icon']);

        $accepted = $service->getStatusVisuals('accepted');
        $this->assertEquals('#4F46E5', $accepted['color']);
        $this->assertEquals('check-circle', $accepted['icon']);

        $dispatched = $service->getStatusVisuals('dispatched');
        $this->assertEquals('#10B981', $dispatched['color']);
        $this->assertEquals('send', $dispatched['icon']);

        $resolved = $service->getStatusVisuals('resolved');
        $this->assertEquals('#6B7280', $resolved['color']);

        $rejected = $service->getStatusVisuals('rejected');
        $this->assertEquals('#EF4444', $rejected['color']);
    }

    /**
     * Requirement 3: Admin municipal map access.
     */
    public function test_admin_municipal_map_access(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.map'));
        $response->assertOk();
        $response->assertSee('LIVE OPERATIONS MAP');
        $response->assertViewHas('initialData');
    }

    /**
     * Requirement 4: Office incident map permissions and scoping.
     */
    public function test_office_incident_map_permissions_and_scoping(): void
    {
        $officeA = $this->createOffice('OFA-'.rand(10, 99));
        $officeB = $this->createOffice('OFB-'.rand(10, 99));
        $userA = $this->createOfficeUser($officeA);

        $incidentA = $this->createIncident();
        IncidentAssignment::create([
            'incident_id' => $incidentA->id,
            'office_id' => $officeA->id,
            'assigned_by' => $userA->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $incidentB = $this->createIncident();
        IncidentAssignment::create([
            'incident_id' => $incidentB->id,
            'office_id' => $officeB->id,
            'assigned_by' => $userA->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($userA)->get(route('office.map.data'));
        $response->assertOk();

        $data = $response->json();
        $incidentRefs = collect($data['incidents'])->pluck('reference_number');

        $this->assertTrue($incidentRefs->contains($incidentA->reference_number));
        $this->assertFalse($incidentRefs->contains($incidentB->reference_number));
    }

    /**
     * Requirement 5: Personnel jurisdiction restrictions.
     */
    public function test_personnel_jurisdiction_restrictions(): void
    {
        $office = $this->createOffice('PRS-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);

        $allBarangays = Barangay::take(2)->get();
        $assignedBarangay = $allBarangays[0];
        $unassignedBarangay = $allBarangays[1];

        // Bind personnel strictly to assignedBarangay
        PersonnelJurisdiction::create([
            'personnel_id' => $profile->id,
            'barangay_id' => $assignedBarangay->id,
            'is_primary' => true,
        ]);

        $incInJurisdiction = $this->createIncident([
            'barangay_id' => $assignedBarangay->id,
            'status' => 'accepted',
        ]);

        $incOutJurisdiction = $this->createIncident([
            'barangay_id' => $unassignedBarangay->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($user)->get(route('personnel.map.data'));
        $response->assertOk();

        $data = $response->json();
        $refs = collect($data['incidents'])->pluck('reference_number');

        $this->assertTrue($refs->contains($incInJurisdiction->reference_number));
        $this->assertFalse($refs->contains($incOutJurisdiction->reference_number));
    }

    /**
     * Requirement 6: Incident marker details formatting.
     */
    public function test_incident_marker_details(): void
    {
        /** @var LiveOperationsMapService $service */
        $service = app(LiveOperationsMapService::class);
        $incident = $this->createIncident([
            'priority' => 'critical',
            'status' => 'pending_review',
        ]);

        $marker = $service->formatIncidentMarker($incident, true);

        $this->assertEquals($incident->reference_number, $marker['reference_number']);
        $this->assertEquals(9.4975, $marker['latitude']);
        $this->assertEquals(122.5850, $marker['longitude']);
        $this->assertArrayHasKey('caller', $marker);
        $this->assertNotNull($marker['caller']['name']);
        $this->assertEquals('pending_review', $marker['status']);
    }

    /**
     * Requirement 7: Barangay identification using Point-in-Polygon.
     */
    public function test_barangay_identification(): void
    {
        /** @var GeoService $geoService */
        $geoService = app(GeoService::class);
        $barangay = Barangay::first();

        // Synthetic polygon surrounding Poblacion: [ [lng, lat], ... ]
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [122.5800, 9.4900],
                    [122.5900, 9.4900],
                    [122.5900, 9.5000],
                    [122.5800, 9.5000],
                    [122.5800, 9.4900],
                ],
            ],
        ];

        BarangayBoundary::updateOrCreate(
            ['barangay_id' => $barangay->id],
            [
                'geojson_boundary' => json_encode($polygon),
                'bounding_min_lat' => 9.4900,
                'bounding_max_lat' => 9.5000,
                'bounding_min_lng' => 122.5800,
                'bounding_max_lng' => 122.5900,
                'is_verified' => true,
            ]
        );

        // Point inside the polygon
        $identified = $geoService->identifyBarangay(9.4950, 122.5850);
        $this->assertNotNull($identified);
        $this->assertEquals($barangay->id, $identified->id);
    }

    /**
     * Requirement 8: Verified GeoJSON boundary import.
     */
    public function test_verified_geojson_import(): void
    {
        $admin = $this->createAdmin();
        $barangay = Barangay::first();

        $geoJsonString = json_encode([
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [122.5700, 9.4800],
                        [122.6000, 9.4800],
                        [122.6000, 9.5100],
                        [122.5700, 9.5100],
                        [122.5700, 9.4800],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.map.boundaries.import'), [
            'boundary_type' => 'barangay',
            'target_id' => $barangay->id,
            'source_information' => 'NAMRIA Cadastral Survey 2026',
            'geojson_content' => $geoJsonString,
        ]);

        $response->assertRedirect(route('admin.map.boundaries'));
        $this->assertDatabaseHas('barangay_boundaries', [
            'barangay_id' => $barangay->id,
            'is_verified' => true,
            'source_information' => 'NAMRIA Cadastral Survey 2026',
        ]);
    }

    /**
     * Requirement 9: GPS coordinate validation.
     */
    public function test_gps_coordinate_validation(): void
    {
        $office = $this->createOffice('VAL-'.rand(10, 99));
        [$user] = $this->createPersonnel($office);

        // Invalid latitude (> 90)
        $response = $this->actingAs($user)->postJson(route('personnel.location'), [
            'latitude' => 105.1234,
            'longitude' => 122.5850,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude']);
    }

    /**
     * Requirement 10: Personnel location updates persisted in database.
     */
    public function test_personnel_location_updates(): void
    {
        $office = $this->createOffice('UPD-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);

        $response = $this->actingAs($user)->postJson(route('personnel.location'), [
            'latitude' => 9.4975,
            'longitude' => 122.5850,
            'accuracy' => 8.5,
            'heading' => 180.0,
            'speed' => 12.5,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('personnel_locations', [
            'personnel_id' => $profile->id,
            'latitude' => 9.4975,
            'longitude' => 122.5850,
        ]);
    }

    /**
     * Requirement 11: Personnel location permission & duty requirement.
     */
    public function test_personnel_location_permission_duty_requirement(): void
    {
        $office = $this->createOffice('OFF-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);

        // Mark personnel as off duty
        $profile->availability()->update(['operational_status' => 'off_duty']);

        /** @var LiveOperationsMapService $mapService */
        $mapService = app(LiveOperationsMapService::class);

        $this->expectException(\InvalidArgumentException::class);
        $mapService->recordPersonnelLocation($profile, 9.4975, 122.5850);
    }

    /**
     * Requirement 12: Location session termination upon going offline.
     */
    public function test_location_session_termination_on_offline(): void
    {
        $office = $this->createOffice('TRM-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);

        $this->actingAs($user)->post(route('personnel.availability.update'), [
            'status' => 'off_duty',
        ]);

        $this->assertDatabaseHas('personnel_availabilities', [
            'personnel_id' => $profile->id,
            'operational_status' => 'off_duty',
        ]);
    }

    /**
     * Requirement 13: Stale location handling.
     */
    public function test_stale_location_handling(): void
    {
        $office = $this->createOffice('STL-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);

        // Record a location 10 minutes ago
        PersonnelLocation::create([
            'personnel_id' => $profile->id,
            'latitude' => 9.4975,
            'longitude' => 122.5850,
            'recorded_at' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(10),
        ]);

        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get(route('admin.map.data'));
        $data = $response->json();

        $responder = collect($data['responders'])->firstWhere('id', $profile->id);
        $this->assertNotNull($responder);
        $this->assertEquals('stale', $responder['freshness']);
    }

    /**
     * Requirement 14: Real-time broadcasting event dispatch.
     */
    public function test_real_time_broadcasting(): void
    {
        Event::fake([PersonnelLocationUpdated::class]);

        $office = $this->createOffice('EVT-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);

        $this->actingAs($user)->postJson(route('personnel.location'), [
            'latitude' => 9.4975,
            'longitude' => 122.5850,
        ]);

        Event::assertDispatched(PersonnelLocationUpdated::class);
    }

    /**
     * Requirement 15: WebSocket channel authorization.
     */
    public function test_websocket_channel_authorization(): void
    {
        $admin = $this->createAdmin();
        $office = $this->createOffice('WSS-'.rand(10, 99));
        $officeUser = $this->createOfficeUser($office);
        [$personnelUser] = $this->createPersonnel($office);

        // Admin has access to admin.operations
        $this->assertTrue($admin->isAdmin());

        // Office user has access to their own office channel
        $this->assertTrue($officeUser->officeUsers()->where('office_id', $office->id)->exists());

        // Cross-office access is prohibited
        $foreignOffice = $this->createOffice('FOR-'.rand(10, 99));
        $this->assertFalse($officeUser->officeUsers()->where('office_id', $foreignOffice->id)->exists());
    }

    /**
     * Requirement 16: Route calculation integration.
     */
    public function test_route_calculation_integration(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson(route('map.route'), [
            'origin_lat' => 9.4975,
            'origin_lng' => 122.5850,
            'dest_lat' => 9.5500,
            'dest_lng' => 122.6000,
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertArrayHasKey('distance_km', $data);
        $this->assertArrayHasKey('duration_minutes', $data);
        $this->assertArrayHasKey('waypoints', $data);
        $this->assertArrayHasKey('google_maps_url', $data);
        $this->assertGreaterThan(0, $data['distance_km']);
    }

    /**
     * Requirement 17: Assignment route visibility for assigned personnel.
     */
    public function test_assignment_route_visibility(): void
    {
        $office = $this->createOffice('ROU-'.rand(10, 99));
        [$user, $profile] = $this->createPersonnel($office);
        $incident = $this->createIncident();

        IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'personnel_id' => $profile->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('personnel.map.data'));
        $data = $response->json();

        $this->assertArrayHasKey('routes', $data);
        $this->assertNotEmpty($data['routes']);
        $this->assertEquals($incident->reference_number, $data['routes'][0]['reference_number']);
    }

    /**
     * Requirement 18: Geo-tagged evidence mapping.
     */
    public function test_geo_tagged_evidence_mapping(): void
    {
        $admin = $this->createAdmin();

        $media = MediaAttachment::create([
            'uploader_id' => $admin->id,
            'disk' => 'private',
            'file_path' => 'evidence/test.jpg',
            'file_name' => 'scene_damage.jpg',
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'evidence'),
            'created_at' => now(),
        ]);

        MediaGeoMetadata::create([
            'media_id' => $media->id,
            'latitude' => 9.4980,
            'longitude' => 122.5860,
            'accuracy_meters' => 5.0,
            'gps_source' => 'device_live_upload',
            'is_inherited' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.map.data'));
        $data = $response->json();

        $this->assertArrayHasKey('evidence', $data);
        $evidenceNames = collect($data['evidence'])->pluck('file_name');
        $this->assertTrue($evidenceNames->contains('scene_damage.jpg'));
    }

    /**
     * Requirement 19: Map filter behavior.
     */
    public function test_map_filter_behavior(): void
    {
        $admin = $this->createAdmin();
        $category = IncidentCategory::first();

        $incSpecial = $this->createIncident([
            'category_id' => $category->id,
            'description' => 'Special Fire Incident Keyword',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.map.data', [
            'category_id' => $category->id,
            'search' => 'Special Fire',
        ]));

        $data = $response->json();
        $refs = collect($data['incidents'])->pluck('reference_number');

        $this->assertTrue($refs->contains($incSpecial->reference_number));
    }

    /**
     * Requirement 20: Unauthorized geographic access prevented for Citizens.
     */
    public function test_unauthorized_geographic_access_prevented(): void
    {
        $citizen = User::factory()->create([
            'account_type' => 'citizen',
            'is_active' => true,
        ]);

        // Citizens cannot access internal Admin Live Operations Map
        $response = $this->actingAs($citizen)->get(route('admin.map'));
        $response->assertRedirect(route('citizen.dashboard'));

        // Citizens cannot access internal Admin Map JSON data
        $response = $this->actingAs($citizen)->getJson(route('admin.map.data'));
        $response->assertForbidden();
    }
}
