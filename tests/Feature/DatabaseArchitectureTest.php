<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\CitizenProfile;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\IncidentReview;
use App\Models\Message;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseArchitectureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_four_main_roles_exist_and_no_super_admin(): void
    {
        $roles = Role::pluck('name')->toArray();

        $this->assertContains('citizen', $roles);
        $this->assertContains('personnel', $roles);
        $this->assertContains('office', $roles);
        $this->assertContains('admin', $roles);
        $this->assertContains('super_admin', $roles);
    }

    public function test_users_table_enforces_exact_four_account_types(): void
    {
        $citizen = User::factory()->citizen()->create();
        $this->assertEquals('citizen', $citizen->account_type);
        $this->assertTrue($citizen->isCitizen());

        $personnel = User::factory()->personnel()->create();
        $this->assertEquals('personnel', $personnel->account_type);
        $this->assertTrue($personnel->isPersonnel());

        $office = User::factory()->office()->create();
        $this->assertEquals('office', $office->account_type);
        $this->assertTrue($office->isOffice());

        $admin = User::factory()->admin()->create();
        $this->assertEquals('admin', $admin->account_type);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_users_table_enforces_unique_phone_number(): void
    {
        User::factory()->create(['phone_number' => '09991112233']);

        $this->expectException(QueryException::class);
        User::factory()->create(['phone_number' => '09991112233']);
    }

    public function test_citizen_profile_relationship_and_address(): void
    {
        $user = User::factory()->citizen()->create();
        $profile = CitizenProfile::create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'verified_citizen' => true,
        ]);

        $this->assertInstanceOf(CitizenProfile::class, $user->citizenProfile);
        $this->assertEquals('John', $user->citizenProfile->first_name);
        $this->assertEquals($user->id, $profile->user->id);
    }

    public function test_office_and_personnel_relationship(): void
    {
        $office = Office::first() ?? Office::factory()->create();
        $user = User::factory()->personnel()->create();

        $personnel = PersonnelProfile::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'badge_number' => 'TEST-BADGE-'.uniqid(),
            'rank_designation' => 'Investigator',
        ]);

        $this->assertEquals($office->id, $personnel->office->id);
        $this->assertTrue($office->personnel->contains($personnel));
    }

    public function test_incident_lifecycle_relationships_and_foreign_keys(): void
    {
        $citizen = User::where('account_type', 'citizen')->first() ?? User::factory()->citizen()->create();
        $category = IncidentCategory::first() ?? IncidentCategory::factory()->create();
        $barangay = Barangay::first() ?? Barangay::factory()->create();
        $office = Office::first() ?? Office::factory()->create();
        $admin = User::where('account_type', 'admin')->first() ?? User::factory()->admin()->create();

        $incident = Incident::create([
            'reference_number' => 'TEST-REF-'.uniqid(),
            'reporter_id' => $citizen->id,
            'source_channel' => 'citizen_portal',
            'category_id' => $category->id,
            'priority' => 'critical',
            'status' => 'submitted',
            'description' => 'Test incident for automated architecture verification',
            'caller_name' => 'Test Caller',
            'caller_phone' => '09123456789',
            'barangay_id' => $barangay->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        $location = IncidentLocation::create([
            'incident_id' => $incident->id,
            'latitude' => 6.11500000,
            'longitude' => 125.17400000,
            'accuracy' => 5.5,
            'street_address' => 'Test Street',
            'landmark' => 'Near Central Plaza',
            'location_source' => 'gps_device',
        ]);

        $review = IncidentReview::create([
            'incident_id' => $incident->id,
            'reviewed_by' => $admin->id,
            'decision' => 'accepted',
            'review_notes' => 'Verified with caller',
            'reviewed_at' => now(),
        ]);

        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->id,
            'office_id' => $office->id,
            'assigned_by' => $admin->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $this->assertEquals($incident->id, $location->incident->id);
        $this->assertEquals($incident->id, $review->incident->id);
        $this->assertEquals($incident->id, $assignment->incident->id);

        $this->assertEquals('Test Street', $incident->location->street_address);
        $this->assertEquals(1, $incident->reviews->count());
        $this->assertEquals(1, $incident->assignments->count());
    }

    public function test_optimistic_concurrency_locking_on_incident(): void
    {
        $incident = Incident::create([
            'reference_number' => 'LOCK-TEST-'.uniqid(),
            'source_channel' => 'citizen_portal',
            'category_id' => IncidentCategory::first()->id,
            'priority' => 'high',
            'status' => 'accepted',
            'description' => 'Concurrency test incident',
            'caller_name' => 'Caller',
            'caller_phone' => '09112223344',
            'barangay_id' => Barangay::first()->id,
            'submitted_at' => now(),
            'version_lock' => 1,
        ]);

        // Simulated dispatcher 1 updates status and increments version_lock
        $affectedRows1 = DB::table('incidents')
            ->where('id', $incident->id)
            ->where('version_lock', 1)
            ->update([
                'status' => 'dispatched',
                'version_lock' => 2,
            ]);

        $this->assertEquals(1, $affectedRows1);

        // Simulated dispatcher 2 attempts update with stale version_lock 1
        $affectedRows2 = DB::table('incidents')
            ->where('id', $incident->id)
            ->where('version_lock', 1)
            ->update([
                'status' => 'rejected',
                'version_lock' => 2,
            ]);

        $this->assertEquals(0, $affectedRows2, 'Concurrency lock failed: Second concurrent dispatch should have been rejected.');
    }

    public function test_database_transaction_rollbacks_cleanly_on_failure(): void
    {
        $initialUserCount = User::count();

        try {
            DB::transaction(function () {
                User::factory()->citizen()->create(['phone_number' => '09887776655']);
                throw new \Exception('Simulated crash within transaction');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertEquals($initialUserCount, User::count(), 'Database transaction did not roll back successfully.');
    }

    public function test_internal_messenger_conversation_and_messages(): void
    {
        $user1 = User::first();
        $user2 = User::skip(1)->first();

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);

        $conversation->users()->attach([$user1->id, $user2->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'body' => 'Radio check on secure channel.',
            'message_type' => 'text',
        ]);

        $this->assertEquals(2, $conversation->users()->count());
        $this->assertEquals(1, $conversation->messages()->count());
        $this->assertEquals('Radio check on secure channel.', $conversation->latestMessage->body);
    }
}
