<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name', 128);
            $table->string('middle_name', 128)->nullable();
            $table->string('last_name', 128);
            $table->string('suffix', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 32)->nullable();
            $table->string('blood_type', 8)->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 32)->nullable();
            $table->boolean('verified_citizen')->default(false);
            $table->string('id_type', 64)->nullable();
            $table->string('id_number_hash', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('citizen_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->restrictOnDelete();
            $table->string('street_address', 255);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_target', 128)->index();
            $table->enum('channel', ['sms', 'email']);
            $table->string('token_hash', 255);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_verifications');
        Schema::dropIfExists('citizen_addresses');
        Schema::dropIfExists('citizen_profiles');
    }
};
