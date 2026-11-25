<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('slug', 64)->unique();
            $table->text('description')->nullable();
            $table->enum('default_priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->string('icon', 64)->nullable();
            $table->string('color_code', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 32)->unique()->index();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source_channel', ['citizen_portal', 'facebook_messenger', 'admin_encoded'])->index();
            $table->foreignId('category_id')->constrained('incident_categories')->restrictOnDelete();
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium')->index();
            $table->enum('status', [
                'submitted', 'pending_review', 'accepted', 'dispatched',
                'acknowledged', 'on_the_way', 'on_scene', 'in_progress',
                'resolved', 'closed', 'rejected',
            ])->default('submitted')->index();
            $table->text('description');
            $table->string('caller_name', 128);
            $table->string('caller_phone', 32)->index();
            $table->foreignId('barangay_id')->constrained('barangays')->restrictOnDelete();
            $table->timestamp('submitted_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('version_lock')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->unique()->constrained('incidents')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8)->index();
            $table->decimal('longitude', 11, 8)->index();
            $table->decimal('accuracy', 6, 2)->nullable();
            $table->string('street_address', 255)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->enum('location_source', ['gps_device', 'map_picker', 'fb_shared_location', 'admin_entered'])->default('gps_device');
        });

        Schema::create('incident_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->enum('decision', ['accepted', 'rejected'])->index();
            $table->string('rejection_reason', 255)->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->index();
        });

        Schema::create('incident_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('response_teams')->nullOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel_profiles')->nullOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->enum('assignment_type', ['automatic_rule', 'manual'])->default('manual');
            $table->enum('status', ['active', 'reassigned', 'completed', 'cancelled'])->default('active')->index();
            $table->timestamp('assigned_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
        });

        Schema::create('incident_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('incident_categories')->cascadeOnDelete();
            $table->foreignId('barangay_id')->nullable()->constrained('barangays')->cascadeOnDelete();
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->nullable();
            $table->foreignId('target_office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('fallback_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->boolean('auto_dispatch')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('incident_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('previous_status', 32);
            $table->string('new_status', 32)->index();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason', 255)->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('response_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('incident_assignments')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel_profiles')->restrictOnDelete();
            $table->enum('milestone', ['acknowledged', 'on_the_way', 'on_scene', 'in_progress', 'cleared'])->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->index();
        });

        Schema::create('incident_evidence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media_attachments')->cascadeOnDelete();
            $table->enum('evidence_type', ['citizen_submission', 'responder_on_scene', 'damage_assessment', 'resolution_proof'])->index();
            $table->string('caption', 255)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_evidence_records');
        Schema::dropIfExists('response_logs');
        Schema::dropIfExists('incident_status_histories');
        Schema::dropIfExists('incident_routing_rules');
        Schema::dropIfExists('incident_assignments');
        Schema::dropIfExists('incident_reviews');
        Schema::dropIfExists('incident_locations');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('incident_categories');
    }
};
