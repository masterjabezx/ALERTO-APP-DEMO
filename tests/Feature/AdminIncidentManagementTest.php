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
use App\Models\Notification;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminIncidentService;
use App\Services\IncidentRoutingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class AdminIncidentManagementTest extends TestCase
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

    private function createOfficeAdmin(Office $office): User
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
            'office_level' => 'office_admin',
            'can_review_incidents' => true,
            'can_dispatch_units' => true,
        ]);

        return $user;
    }

    private function createOfficeStaff(Office $office): User
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
            'office_level' => 'office_staff',
            'can_review_incidents' => false,
            'can_dispatch_units' => false,
        ]);

        return $user;
    }

    private function createPersonnel(Office $office, string $status = 'available'): PersonnelProfile
    {
        $user = User::factory()->create([
            'account_type' => 'personnel',
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
        $role = Role::where('name', 'personnel')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
        $profile = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'TEST-BADGE-'.rand(100, 999),
            'rank_designation' => 'Responder',
            'specialization' => 'First Aid',
        ]);

        PersonnelAvailability::create([
            'personnel_id' => $profile->id,
            'operational_status' => $status,
            'status_updated_at' => now(),
        ]);

        return $profile;
    }

    private function createCitizen(): User
    {
        return User::factory()->create([
            'account_type' => 'citizen',
            'is_active' => true,
            'phone_number' => '0917'.rand(1000000, 9999999),
        ]);
    }

    private function createIncident(array $overrides = []): Incident
    {
        $category = IncidentCategory::first() ?? IncidentCategory::create([
            'name' => 'Fire Emergency',
            'slug' => 'fire-emergency',
            'default_priority' => 'critical',
            'is_active' => true,
        ]);
        $barangay = Barangay::first();
        $citizen = $overrides['reporter'] ?? $this->createCitizen();

        $incident = Incident::create(array_merge([
            'reference_number' => 'INC-2026-'.rand(1000, 9999).'-TEST',
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'critical',
            'status' => 'pending_review',
            'description' => 'Test emergency incident description.',
            'caller_name' => $citizen->name,
            'caller_phone' => $citizen->phone_number,
            'barangay_id' => $barangay ? $barangay->id : 1,
            'submitted_at' => now(),
            'version_lock' => 1,
        ], $overrides));

        IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 9.5936,
            'longitude' => 122.4542,
            'accuracy' => 10.0,
            'location_source' => 'gps_device',
        ]);

        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'previous_status' => 'submitted',
            'new_status' => $incident->status,
            'changed_by' => $citizen->id,
            'change_reason' => 'Initial submission via citizen intake.',
            'created_at' => now(),
        ]);

        return $incident;
    }

    /**
     * 1. Citizen report appears in Admin pending queue.
     */
    public function test_citizen_report_appears_in_admin_pending_queue(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($admin)->get(route('admin.incidents.index', ['tab' => 'pending_review']));

        $response->assertStatus(200);
        $response->assertSee($incident->reference_number);
        $response->assertSee('PENDING REVIEW');
    }

    /**
     * 2. Admin can view incident details.
     */
    public function test_admin_can_view_incident_details(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident();

        $response = $this->actingAs($admin)->get(route('admin.incidents.show', $incident->reference_number));

        $response->assertStatus(200);
        $response->assertSee($incident->reference_number);
        $response->assertSee($incident->caller_name);
        $response->assertSee($incident->caller_phone);
        $response->assertSee('Location &amp; Coordinates', false);
    }

    /**
     * 3. Authorized Office Admin can review permitted reports.
     */
    public function test_authorized_office_admin_can_review_permitted_reports(): void
    {
        $office = Office::first();
        $officeAdmin = $this->createOfficeAdmin($office);
        $category = IncidentCategory::first();

        // Ensure routing rule targets this office
        IncidentRoutingRule::updateOrCreate(
            ['category_id' => $category->id],
            ['target_office_id' => $office->id, 'is_active' => true]
        );

        $incident = $this->createIncident([
            'category_id' => $category->id,
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($officeAdmin)->post(route('office.incidents.review', $incident->reference_number), [
            'decision' => 'accepted',
            'review_notes' => 'Verified by Office Admin desk.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('accepted', $incident->fresh()->status);
    }

    /**
     * 4. Unauthorized Office Staff cannot accept reports.
     */
    public function test_unauthorized_office_staff_cannot_accept_reports(): void
    {
        $office = Office::first();
        $officeStaff = $this->createOfficeStaff($office);
        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($officeStaff)->post(route('office.incidents.review', $incident->reference_number), [
            'decision' => 'accepted',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('pending_review', $incident->fresh()->status);
    }

    /**
     * 5. Admin can accept reports.
     */
    public function test_admin_can_accept_reports(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($admin)->post(route('admin.incidents.review', $incident->reference_number), [
            'decision' => 'accepted',
            'review_notes' => 'Triage verified. Urgent fire response required.',
        ]);

        $response->assertRedirect(route('admin.incidents.show', $incident->reference_number));
        $fresh = $incident->fresh();
        $this->assertEquals('accepted', $fresh->status);
        $this->assertNotNull($fresh->accepted_at);

        // Check review record
        $this->assertDatabaseHas('incident_reviews', [
            'incident_id' => $incident->id,
            'reviewed_by' => $admin->id,
            'decision' => 'accepted',
        ]);
    }

    /**
     * 6. Admin can reject reports.
     */
    public function test_admin_can_reject_reports(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($admin)->post(route('admin.incidents.review', $incident->reference_number), [
            'decision' => 'rejected',
            'rejection_reason' => 'Prank / Malicious Report',
            'review_notes' => 'No active fire found at coordinates upon operator verification call.',
        ]);

        $response->assertRedirect(route('admin.incidents.show', $incident->reference_number));
        $this->assertEquals('rejected', $incident->fresh()->status);

        $this->assertDatabaseHas('incident_reviews', [
            'incident_id' => $incident->id,
            'decision' => 'rejected',
            'rejection_reason' => 'Prank / Malicious Report',
        ]);
    }

    /**
     * 7. Rejection requires a reason.
     */
    public function test_rejection_requires_a_reason(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($admin)->post(route('admin.incidents.review', $incident->reference_number), [
            'decision' => 'rejected',
            'rejection_reason' => '',
        ]);

        $response->assertSessionHasErrors('rejection_reason');
        $this->assertEquals('pending_review', $incident->fresh()->status);
    }

    /**
     * 8. Contact verification history is recorded.
     */
    public function test_contact_verification_history_is_recorded(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident();

        $response = $this->actingAs($admin)->post(route('admin.incidents.verify-contact', $incident->reference_number), [
            'contact_number' => $incident->caller_phone,
            'verification_method' => 'callback',
            'status' => 'verified',
            'remarks' => 'Spoke directly with caller. Emergency confirmed.',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('incident_contact_verifications', [
            'incident_id' => $incident->id,
            'contact_number' => $incident->caller_phone,
            'verification_method' => 'callback',
            'status' => 'verified',
            'verified_by' => $admin->id,
        ]);

        $this->assertEquals('verified', $incident->fresh()->contact_verification_status);
    }

    /**
     * 9. Automatic routing recommendations work.
     */
    public function test_automatic_routing_recommendations_work(): void
    {
        $office = Office::where('department_type', 'fire')->first() ?? Office::first();
        $category = IncidentCategory::where('slug', 'fire-incident')->first() ?? IncidentCategory::first();

        IncidentRoutingRule::updateOrCreate(
            ['category_id' => $category->id],
            [
                'target_office_id' => $office->id,
                'is_active' => true,
            ]
        );

        $incident = $this->createIncident([
            'category_id' => $category->id,
            'status' => 'accepted',
        ]);

        $routingService = app(IncidentRoutingService::class);
        $recommendation = $routingService->recommendRouting($incident);

        $this->assertTrue($recommendation['rule_matched']);
        $this->assertNotNull($recommendation['recommended_office']);
        $this->assertEquals($office->id, $recommendation['recommended_office']->id);
    }

    /**
     * 10. Manual assignment overrides work.
     */
    public function test_manual_assignment_overrides_work(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'accepted']);
        $differentOffice = Office::latest('id')->first();
        $team = $differentOffice->teams()->first();

        $response = $this->actingAs($admin)->post(route('admin.incidents.assign', $incident->reference_number), [
            'office_id' => $differentOffice->id,
            'team_id' => $team?->id,
            'notes' => 'Manual dispatch override by command director.',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('incident_assignments', [
            'incident_id' => $incident->id,
            'office_id' => $differentOffice->id,
            'status' => 'active',
        ]);
    }

    /**
     * 11. Unavailable personnel are not recommended for new dispatch.
     */
    public function test_unavailable_personnel_are_not_recommended_for_new_dispatch(): void
    {
        $incident = $this->createIncident(['status' => 'accepted']);
        $routingService = app(IncidentRoutingService::class);
        $recommendation = $routingService->recommendRouting($incident);
        $targetOffice = $recommendation['recommended_office'];

        $availableResponder = $this->createPersonnel($targetOffice, 'available');
        $offlineResponder = $this->createPersonnel($targetOffice, 'off_duty');

        if ($targetOffice->teams()->exists()) {
            $team = $targetOffice->teams()->first();
            $availableResponder->teams()->syncWithoutDetaching([$team->id => ['assigned_at' => now(), 'role_in_team' => 'responder']]);
            $offlineResponder->teams()->syncWithoutDetaching([$team->id => ['assigned_at' => now(), 'role_in_team' => 'responder']]);
        }

        // Re-evaluate routing with the new responders in target office
        $recommendation = $routingService->recommendRouting($incident);

        $eligibleIds = collect($recommendation['eligible_personnel'])->pluck('id');
        $offlineIds = collect($recommendation['offline_personnel'])->pluck('id');

        $this->assertTrue($eligibleIds->contains($availableResponder->id));
        $this->assertFalse($eligibleIds->contains($offlineResponder->id));
        $this->assertTrue($offlineIds->contains($offlineResponder->id));
    }

    /**
     * 12. Duplicate assignments are prevented.
     */
    public function test_duplicate_assignments_are_prevented(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'accepted']);
        $office = Office::first();

        // First assignment succeeds
        $this->actingAs($admin)->post(route('admin.incidents.assign', $incident->reference_number), [
            'office_id' => $office->id,
        ]);

        // Second duplicate assignment fails gracefully
        $response = $this->actingAs($admin)->post(route('admin.incidents.assign', $incident->reference_number), [
            'office_id' => $office->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, IncidentAssignment::where('incident_id', $incident->id)->where('office_id', $office->id)->count());
    }

    /**
     * 13. Dispatch cannot happen before acceptance.
     */
    public function test_dispatch_cannot_happen_before_acceptance(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $response = $this->actingAs($admin)->post(route('admin.incidents.dispatch', $incident->reference_number));

        $response->assertSessionHas('error');
        $this->assertEquals('pending_review', $incident->fresh()->status);
    }

    /**
     * 14. Dispatch cannot happen without confirmed assignment.
     */
    public function test_dispatch_cannot_happen_without_confirmed_assignment(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'accepted']);

        // No assignment made yet
        $response = $this->actingAs($admin)->post(route('admin.incidents.dispatch', $incident->reference_number));

        $response->assertSessionHas('error');
        $this->assertEquals('accepted', $incident->fresh()->status);
    }

    /**
     * 15. Only assigned recipients receive dispatch alerts.
     */
    public function test_only_assigned_recipients_receive_dispatch_alerts(): void
    {
        $admin = $this->createAdmin();
        $office = Office::first();
        $assignedResponder = $this->createPersonnel($office, 'available');
        $unassignedResponder = $this->createPersonnel($office, 'available');

        $incident = $this->createIncident(['status' => 'accepted']);

        // Assign only to assignedResponder
        $routingService = app(IncidentRoutingService::class);
        $routingService->assignIncident($admin, $incident, [
            'office_id' => $office->id,
            'personnel_id' => $assignedResponder->id,
        ]);

        // Dispatch
        $routingService->dispatchIncident($admin, $incident);

        $this->assertEquals('dispatched', $incident->fresh()->status);

        // Assigned responder received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $assignedResponder->user_id,
            'type' => 'incident_dispatch_order',
        ]);

        // Unassigned responder received ZERO dispatch notifications
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $unassignedResponder->user_id,
            'type' => 'incident_dispatch_order',
        ]);
    }

    /**
     * 16. Incident status history is recorded.
     */
    public function test_incident_status_history_is_recorded(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $this->actingAs($admin)->post(route('admin.incidents.review', $incident->reference_number), [
            'decision' => 'accepted',
            'review_notes' => 'Status transition verified.',
        ]);

        $this->assertDatabaseHas('incident_status_histories', [
            'incident_id' => $incident->id,
            'previous_status' => 'pending_review',
            'new_status' => 'accepted',
            'changed_by' => $admin->id,
        ]);
    }

    /**
     * 17. Citizens receive permitted status notifications.
     */
    public function test_citizens_receive_permitted_status_notifications(): void
    {
        $admin = $this->createAdmin();
        $citizen = $this->createCitizen();
        $incident = $this->createIncident([
            'reporter_id' => $citizen->id,
            'status' => 'pending_review',
        ]);

        $this->actingAs($admin)->post(route('admin.incidents.review', $incident->reference_number), [
            'decision' => 'accepted',
            'review_notes' => 'Accepted by triage.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $citizen->id,
            'type' => 'incident_accepted',
        ]);
    }

    /**
     * 18. Unauthorized users cannot access other incident records.
     */
    public function test_unauthorized_users_cannot_access_other_incident_records(): void
    {
        $citizen1 = $this->createCitizen();
        $citizen2 = $this->createCitizen();
        $incident = $this->createIncident(['reporter_id' => $citizen1->id]);

        $response = $this->actingAs($citizen2)->get(route('citizen.records.show', $incident->reference_number));
        $response->assertStatus(404);
    }

    /**
     * 19. Concurrent review actions do not create inconsistent states.
     */
    public function test_concurrent_review_actions_do_not_create_inconsistent_states(): void
    {
        $office = Office::first();
        $reviewer1 = $this->createOfficeAdmin($office);
        $incident = $this->createIncident(['status' => 'pending_review']);

        // First review accepts
        $adminIncidentService = app(AdminIncidentService::class);
        $adminIncidentService->reviewIncident($reviewer1, $incident, 'accepted', null, 'First review');

        $this->assertEquals('accepted', $incident->fresh()->status);

        // Attempting a second review on an already accepted incident by non-admin must fail
        $reviewer2 = $this->createOfficeAdmin($office);
        $this->expectException(InvalidArgumentException::class);
        $adminIncidentService->reviewIncident($reviewer2, $incident->fresh(), 'rejected', 'Second conflicting review');
    }

    /**
     * 20. Dashboard statistics use real database records.
     */
    public function test_dashboard_statistics_use_real_database_records(): void
    {
        $admin = $this->createAdmin();
        $incident = $this->createIncident(['status' => 'pending_review']);

        $service = app(AdminIncidentService::class);
        $stats = $service->getIncidentStats();

        $this->assertGreaterThanOrEqual(1, $stats['total']);
        $this->assertGreaterThanOrEqual(1, $stats['pending_review']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee($incident->reference_number);
    }
}
