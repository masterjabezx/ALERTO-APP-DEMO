<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->restrictOnDelete();
            $table->string('base_name', 128);
            $table->string('address', 255);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('contact_phone', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('response_teams', function (Blueprint $table) {
            $table->text('description')->nullable()->after('team_type');
            $table->foreignId('response_base_id')->nullable()->after('description')->constrained('response_bases')->nullOnDelete();
            $table->foreignId('team_leader_id')->nullable()->after('response_base_id')->constrained('personnel_profiles')->nullOnDelete();
            $table->json('authorized_categories')->nullable()->after('team_leader_id');
        });

        Schema::table('personnel_profiles', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique()->after('badge_number');
            $table->string('profile_photo_path', 512)->nullable()->after('is_team_leader');
            $table->json('authorized_categories')->nullable()->after('profile_photo_path');
        });

        Schema::create('team_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('response_teams')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'barangay_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_jurisdictions');

        Schema::table('personnel_profiles', function (Blueprint $table) {
            $table->dropColumn(['username', 'profile_photo_path', 'authorized_categories']);
        });

        Schema::table('response_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_leader_id');
            $table->dropConstrainedForeignId('response_base_id');
            $table->dropColumn(['description', 'authorized_categories']);
        });

        Schema::dropIfExists('response_bases');
    }
};
