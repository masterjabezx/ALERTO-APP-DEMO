<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->restrictOnDelete();
            $table->string('badge_number', 64)->unique()->index();
            $table->string('rank_designation', 128);
            $table->string('specialization', 128)->nullable();
            $table->boolean('is_team_leader')->default(false);
            $table->timestamps();
        });

        Schema::create('personnel_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel_profiles')->cascadeOnDelete();
            $table->enum('operational_status', ['available', 'dispatched', 'on_scene', 'busy', 'off_duty'])->default('off_duty')->index();
            $table->string('status_reason', 255)->nullable();
            $table->timestamp('status_updated_at')->index();
        });

        Schema::create('availability_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel_profiles')->cascadeOnDelete();
            $table->timestamp('shift_started_at');
            $table->timestamp('shift_ended_at')->nullable();
            $table->string('starting_status', 32);
            $table->string('ending_status', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('response_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->restrictOnDelete();
            $table->string('team_name', 128);
            $table->string('call_sign', 64)->unique();
            $table->string('team_type', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('response_teams')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel_profiles')->cascadeOnDelete();
            $table->string('role_in_team', 64)->default('member');
            $table->timestamp('assigned_at');
            $table->timestamps();
            $table->unique(['team_id', 'personnel_id']);
        });

        Schema::create('personnel_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel_profiles')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->unique(['personnel_id', 'barangay_id']);
        });

        Schema::create('personnel_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel_profiles')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('heading', 5, 2)->nullable();
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('accuracy', 6, 2)->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_locations');
        Schema::dropIfExists('personnel_jurisdictions');
        Schema::dropIfExists('team_memberships');
        Schema::dropIfExists('response_teams');
        Schema::dropIfExists('availability_sessions');
        Schema::dropIfExists('personnel_availabilities');
        Schema::dropIfExists('personnel_profiles');
    }
};
