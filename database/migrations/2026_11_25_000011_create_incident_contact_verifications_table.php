<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_contact_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('contact_number', 32);
            $table->enum('verification_method', ['callback', 'otp_sms'])->default('callback');
            $table->enum('status', ['unverified', 'verification_pending', 'verified', 'failed_verification'])->default('unverified');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->enum('contact_verification_status', ['unverified', 'verification_pending', 'verified', 'failed_verification'])
                ->default('unverified')
                ->after('caller_phone')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('contact_verification_status');
        });

        Schema::dropIfExists('incident_contact_verifications');
    }
};
