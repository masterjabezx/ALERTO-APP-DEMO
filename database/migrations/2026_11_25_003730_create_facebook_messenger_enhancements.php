<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fb_integration_settings', function (Blueprint $table) {
            $table->string('app_id', 128)->nullable()->after('page_name');
            $table->boolean('webhook_verified')->default(false)->after('is_active');
            $table->timestamp('last_webhook_at')->nullable()->after('webhook_verified');
            $table->timestamp('last_message_at')->nullable()->after('last_webhook_at');
            $table->text('last_error')->nullable()->after('last_message_at');
        });

        Schema::table('fb_conversations', function (Blueprint $table) {
            $table->unsignedInteger('unread_count')->default(0)->after('last_interaction_at');
            $table->boolean('is_possible_incident')->default(false)->after('unread_count')->index();
            $table->text('latest_message_text')->nullable()->after('is_possible_incident');
        });

        Schema::table('fb_messages', function (Blueprint $table) {
            $table->string('delivery_status', 32)->default('sent')->after('raw_payload')->index();
            $table->text('error_message')->nullable()->after('delivery_status');
        });

        Schema::table('incident_intake_records', function (Blueprint $table) {
            $table->string('status', 32)->default('new')->after('current_step')->index();
            $table->string('location_details', 255)->nullable()->after('collected_barangay_id');
            $table->string('location_source', 64)->default('none')->after('location_details');
            $table->text('rejection_reason')->nullable()->after('generated_incident_id');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('incident_intake_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'status',
                'location_details',
                'location_source',
                'rejection_reason',
                'reviewed_at',
            ]);
        });

        Schema::table('fb_messages', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'error_message']);
        });

        Schema::table('fb_conversations', function (Blueprint $table) {
            $table->dropColumn(['unread_count', 'is_possible_incident', 'latest_message_text']);
        });

        Schema::table('fb_integration_settings', function (Blueprint $table) {
            $table->dropColumn([
                'app_id',
                'webhook_verified',
                'last_webhook_at',
                'last_message_at',
                'last_error',
            ]);
        });
    }
};
