<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('body');
            $table->string('type', 64)->index();
            $table->string('action_url', 512)->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->enum('channel', ['websocket', 'web_push', 'sms', 'email']);
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending')->index();
            $table->string('provider_reference', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('enable_push')->default(true);
            $table->boolean('enable_sms')->default(true);
            $table->boolean('enable_email')->default(false);
            $table->boolean('enable_sound')->default(true);
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique()->index();
            $table->text('value')->nullable();
            $table->string('cast_type', 32)->default('string');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 128);
            $table->string('module', 64)->index();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('system_backup_records', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name', 255);
            $table->string('file_path', 512);
            $table->unsignedBigInteger('file_size_bytes');
            $table->enum('status', ['completed', 'failed', 'in_progress'])->default('in_progress');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backup_records');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('user_notification_settings');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
    }
};
