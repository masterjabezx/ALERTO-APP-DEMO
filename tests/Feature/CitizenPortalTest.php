<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenPortalTest extends TestCase
{
    use DatabaseTransactions;

    private function createCitizen(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'citizen',
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ], $overrides));
    }

    public function test_citizen_dashboard_displays_categories_and_hotline(): void
    {
        $citizen = $this->createCitizen();
        SystemSetting::updateOrCreate(
            ['key' => 'dial_daph_command_center_hotline'],
            ['value' => '0917-123-4567', 'cast_type' => 'string']
        );

        $response = $this->actingAs($citizen)->get(route('citizen.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('DIAL DAPH COMMAND CENTER');
        $response->assertSee('0917-123-4567');
        $response->assertSee('EMERGENCY SOS');
        $response->assertSee('SUBMIT EMERGENCY REPORT TO DIAL DAPH');
    }

    public function test_citizen_can_submit_incident_with_gps_coordinates(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $citizen = $this->createCitizen();
        $barangay = Barangay::first();

        $photo = UploadedFile::fake()->create('scene.jpg', 50, 'image/jpeg');

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'barangay_id' => $barangay->id,
            'caller_phone' => '09171234567',
            'caller_name' => 'Juan Citizen',
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'accuracy' => 15.2,
            'location_source' => 'gps_browser',
            'landmark' => 'Near Public Plaza',
            'attachments' => [$photo],
        ]);

        $response->assertSessionHasNoErrors();

        $incident = Incident::where('reporter_id', $citizen->id)->latest('id')->first();
        $this->assertNotNull($incident);

        $response->assertRedirect(route('citizen.incident.success', ['reference' => $incident->reference_number]));

        // Simplified submission enters unclassified incoming queue for DIAL DAPH call verification
        $this->assertEquals('submitted', $incident->status);
        $this->assertEquals('new_report', $incident->workflow_stage);
        $this->assertNull($incident->category_id);
        $this->assertStringStartsWith('INC-', $incident->reference_number);

        // Check location
        $this->assertDatabaseHas('incident_locations', [
            'incident_id' => $incident->id,
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_device',
        ]);

        // Check evidence and geo-tagging
        $this->assertDatabaseHas('incident_evidence_records', [
            'incident_id' => $incident->id,
        ]);
        $this->assertDatabaseHas('media_geo_metadata', [
            'latitude' => 9.5936,
            'longitude' => 122.4542,
        ]);
    }

    public function test_citizen_can_submit_incident_with_manual_pin(): void
    {
        $citizen = $this->createCitizen();
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'caller_phone' => '09179876543',
            'caller_name' => $citizen->name,
            'latitude' => 9.6150,
            'longitude' => 122.4350,
            'location_source' => 'manual_pin',
            'description' => 'Landslide partial road blockage.',
        ]);

        $response->assertSessionHasNoErrors();

        $incident = Incident::where('reporter_id', $citizen->id)->latest('id')->first();
        $this->assertNotNull($incident);

        $response->assertRedirect(route('citizen.incident.success', ['reference' => $incident->reference_number]));

        $this->assertDatabaseHas('incident_locations', [
            'incident_id' => $incident->id,
            'location_source' => 'map_picker',
        ]);
    }

    public function test_submission_fails_without_required_phone(): void
    {
        $citizen = $this->createCitizen();
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            // caller_phone missing
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_browser',
        ]);

        $response->assertSessionHasErrors('caller_phone');
    }

    public function test_submission_fails_with_invalid_phone_format(): void
    {
        $citizen = $this->createCitizen();
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'caller_phone' => '12345', // invalid format
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_browser',
        ]);

        $response->assertSessionHasErrors('caller_phone');
    }

    public function test_submission_fails_without_category(): void
    {
        $citizen = $this->createCitizen();

        $response = $this->actingAs($citizen)->post(route('citizen.incident.submit'), [
            'caller_name' => $citizen->name,
            'caller_phone' => '09171234567',
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'location_source' => 'gps_browser',
        ]);

        // Simplified reporting explicitly allows submission without selecting a category
        $response->assertSessionHasNoErrors();
        $incident = Incident::where('reporter_id', $citizen->id)->latest('id')->first();
        $this->assertNotNull($incident);
        $this->assertNull($incident->category_id);
        $this->assertEquals('unassessed', $incident->assessment_status);
    }

    public function test_success_screen_renders_with_incident_details(): void
    {
        $citizen = $this->createCitizen();
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-2026-TEST-SUCC',
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'priority' => 'high',
            'status' => 'pending_review',
            'description' => 'Test Emergency',
            'caller_name' => 'Juan Citizen',
            'caller_phone' => '09171234567',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($citizen)->get(route('citizen.incident.success', ['reference' => $incident->reference_number]));

        $response->assertStatus(200);
        $response->assertSee('SUCCESSFUL REPORT SUBMISSION');
        $response->assertSee('INC-2026-TEST-SUCC');
        $response->assertSee('PENDING REVIEW');
        $response->assertSee('DIAL DAPH COMMAND CENTER');
    }

    public function test_citizen_can_view_own_incident_records(): void
    {
        $citizen = $this->createCitizen();
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incident = Incident::create([
            'reference_number' => 'INC-2026-TEST-RECS',
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'priority' => 'high',
            'status' => 'pending_review',
            'description' => 'My Test Emergency',
            'caller_name' => 'Juan Citizen',
            'caller_phone' => '09171234567',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($citizen)->get(route('citizen.records'));

        $response->assertStatus(200);
        $response->assertSee('INC-2026-TEST-RECS');
    }

    public function test_citizen_cannot_view_another_citizens_incident_record(): void
    {
        $citizenA = $this->createCitizen();
        $citizenB = $this->createCitizen();
        $category = IncidentCategory::first();
        $barangay = Barangay::first();

        $incidentB = Incident::create([
            'reference_number' => 'INC-2026-CITIZEN-B',
            'reporter_id' => $citizenB->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'priority' => 'high',
            'status' => 'pending_review',
            'description' => 'Citizen B Private Report',
            'caller_name' => 'Citizen B',
            'caller_phone' => '09170000002',
            'submitted_at' => now(),
        ]);

        // Citizen A attempts to view Citizen B's report -> 404 forbidden/not found
        $response = $this->actingAs($citizenA)->get(route('citizen.records.show', ['reference' => $incidentB->reference_number]));

        $response->assertStatus(404);
    }

    public function test_contacts_page_renders_with_official_hotlines(): void
    {
        $citizen = $this->createCitizen();

        $response = $this->actingAs($citizen)->get(route('citizen.contacts'));

        $response->assertStatus(200);
        $response->assertSee('DIAL DAPH COMMAND CENTER');
        $response->assertSee('Hinoba-an Emergency Hotlines Directory');
        $response->assertSee('Official Facebook Page');
    }
}
