<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add idempotency_key and client_uuid to incidents
        Schema::table('incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('incidents', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('reference_number');
            }
            if (! Schema::hasColumn('incidents', 'client_uuid')) {
                $table->string('client_uuid', 64)->nullable()->index()->after('idempotency_key');
            }
        });

        // 2. Create pwa_sync_logs table
        if (! Schema::hasTable('pwa_sync_logs')) {
            Schema::create('pwa_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('idempotency_key', 64)->unique();
                $table->string('action_type', 48)->index();
                $table->enum('status', ['received', 'processed', 'conflict', 'failed'])->default('received');
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->string('error_message', 500)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_sync_logs');

        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'client_uuid')) {
                $table->dropColumn('client_uuid');
            }
            if (Schema::hasColumn('incidents', 'idempotency_key')) {
                $table->dropColumn('idempotency_key');
            }
        });
    }
};
