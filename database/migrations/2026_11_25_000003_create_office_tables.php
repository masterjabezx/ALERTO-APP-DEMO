<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('code', 32)->unique()->index();
            $table->enum('department_type', ['police', 'fire', 'medical', 'disaster', 'traffic'])->index();
            $table->string('contact_phone', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('office_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('office_level', ['office_admin', 'office_staff'])->default('office_staff')->index();
            $table->boolean('can_review_incidents')->default(false);
            $table->boolean('can_dispatch_units')->default(false);
            $table->timestamps();
            $table->unique(['office_id', 'user_id']);
        });

        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->unique()->constrained('offices')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->restrictOnDelete();
            $table->string('street_address', 255);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('service_radius_meters')->default(5000);
            $table->timestamps();
        });

        Schema::create('office_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->string('category_slug', 64)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('emergency_hotlines', function (Blueprint $table) {
            $table->id();
            $table->string('service_name', 128);
            $table->string('phone_number', 64);
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->boolean('is_toll_free')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_hotlines');
        Schema::dropIfExists('office_responsibilities');
        Schema::dropIfExists('office_locations');
        Schema::dropIfExists('office_users');
        Schema::dropIfExists('offices');
    }
};
