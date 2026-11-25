<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Make minimal citizen/guest reporting fields nullable until Admin classification & verification
        DB::statement("ALTER TABLE incidents MODIFY COLUMN source_channel ENUM('citizen_portal', 'guest_portal', 'facebook_messenger', 'admin_encoded') NOT NULL DEFAULT 'citizen_portal'");
        DB::statement('ALTER TABLE incidents MODIFY COLUMN category_id BIGINT UNSIGNED NULL DEFAULT NULL');
        DB::statement('ALTER TABLE incidents MODIFY COLUMN barangay_id BIGINT UNSIGNED NULL DEFAULT NULL');
        DB::statement("ALTER TABLE incidents MODIFY COLUMN priority ENUM('critical', 'high', 'medium', 'low') NULL DEFAULT NULL");
        DB::statement('ALTER TABLE incidents MODIFY COLUMN description TEXT NULL');

        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('is_guest_report')->default(false)->index()->after('reporter_id');
            $table->string('guest_claim_token_hash', 64)->nullable()->index()->after('is_guest_report');
            $table->timestamp('guest_claimed_at')->nullable()->after('guest_claim_token_hash');
            $table->boolean('is_mobile_number_verified')->default(false)->after('caller_phone');

            $table->enum('workflow_stage', [
                'new_report',
                'awaiting_call',
                'assessment_in_progress',
                'classified_ready',
                'dispatched',
                'active_response',
                'resolved',
                'closed',
                'rejected',
            ])->default('new_report')->index()->after('status');

            $table->enum('assessment_status', [
                'unassessed',
                'call_attempted',
                'in_progress',
                'completed',
            ])->default('unassessed')->index()->after('workflow_stage');

            $table->foreignId('processed_by_admin_id')->nullable()->after('assessment_status')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_for_admin_at')->nullable()->after('processed_by_admin_id');
            $table->timestamp('classified_at')->nullable()->after('accepted_at');
            $table->timestamp('dispatched_at')->nullable()->after('classified_at');
            $table->foreignId('overridden_by_super_admin_id')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->boolean('has_assignment_warning')->default(false)->after('version_lock');
            $table->string('assignment_warning_message', 500)->nullable()->after('has_assignment_warning');
        });

        // 2. Backfill existing incidents cleanly so historical data aligns with new workflow stages
        DB::statement("
            UPDATE incidents SET workflow_stage = CASE
                WHEN status = 'pending_review' THEN 'new_report'
                WHEN status = 'accepted' THEN 'classified_ready'
                WHEN status = 'dispatched' THEN 'dispatched'
                WHEN status IN ('acknowledged', 'on_the_way', 'on_scene', 'in_progress') THEN 'active_response'
                WHEN status = 'resolved' THEN 'resolved'
                WHEN status = 'closed' THEN 'closed'
                WHEN status = 'rejected' THEN 'rejected'
                ELSE 'new_report'
            END,
            assessment_status = CASE
                WHEN status IN ('accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress', 'resolved', 'closed') THEN 'completed'
                ELSE 'unassessed'
            END
        ");

        // 3. Dedicated Emergency Call & Verification Attempts history table
        Schema::create('emergency_call_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->restrictOnDelete();
            $table->string('phone_number_dialed', 32);
            $table->enum('call_outcome', [
                'initiated',
                'answered',
                'no_answer',
                'busy',
                'invalid_number',
                'callback_required',
            ])->default('initiated')->index();
            $table->text('verification_notes')->nullable();
            $table->boolean('location_confirmed')->default(false);
            $table->boolean('category_confirmed')->default(false);
            $table->boolean('callback_required')->default(false);
            $table->timestamp('initiated_at')->index();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });

        // 4. Dedicated Emergency Assessments table for full classified emergency details
        Schema::create('emergency_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->unique()->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('assessed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('incident_categories')->restrictOnDelete();
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->index();
            $table->foreignId('verified_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->text('incident_description');
            $table->unsignedInteger('people_involved_count')->nullable();
            $table->unsignedInteger('injured_count')->nullable();
            $table->unsignedInteger('needing_rescue_count')->nullable();
            $table->unsignedInteger('fatalities_count')->nullable();
            $table->boolean('casualty_counts_unknown')->default(false);
            $table->text('injury_medical_needs')->nullable();
            $table->text('hazards_present')->nullable();
            $table->text('assistance_required')->nullable();
            $table->text('required_equipment')->nullable();
            $table->json('required_office_ids')->nullable();
            $table->json('required_team_ids')->nullable();
            $table->json('required_qualifications')->nullable();
            $table->string('landmark_details', 255)->nullable();
            $table->text('additional_remarks')->nullable();
            $table->boolean('location_confirmed')->default(false);
            $table->boolean('category_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        // 5. Enhance incident_routing_rules for configurable multi-office, team, qualification, and escalation routing
        Schema::table('incident_routing_rules', function (Blueprint $table) {
            $table->json('support_office_ids')->nullable()->after('fallback_office_id');
            $table->foreignId('response_team_id')->nullable()->after('support_office_ids')->constrained('response_teams')->nullOnDelete();
            $table->json('required_qualifications')->nullable()->after('response_team_id');
            $table->unsignedSmallInteger('required_responders_count')->default(1)->after('required_qualifications');
            $table->json('notification_recipients')->nullable()->after('required_responders_count');
            $table->unsignedSmallInteger('escalation_minutes')->nullable()->after('notification_recipients');
            $table->text('escalation_notes')->nullable()->after('escalation_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('incident_routing_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('response_team_id');
            $table->dropColumn([
                'support_office_ids',
                'required_qualifications',
                'required_responders_count',
                'notification_recipients',
                'escalation_minutes',
                'escalation_notes',
            ]);
        });

        Schema::dropIfExists('emergency_assessments');
        Schema::dropIfExists('emergency_call_attempts');

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processed_by_admin_id');
            $table->dropConstrainedForeignId('overridden_by_super_admin_id');
            $table->dropColumn([
                'is_guest_report',
                'guest_claim_token_hash',
                'guest_claimed_at',
                'is_mobile_number_verified',
                'workflow_stage',
                'assessment_status',
                'locked_for_admin_at',
                'classified_at',
                'dispatched_at',
                'has_assignment_warning',
                'assignment_warning_message',
            ]);
        });
    }
};
