<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_id', 128)->unique();
            $table->string('page_name', 255);
            $table->string('verify_token', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fb_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('page_id', 128)->index();
            $table->string('fb_user_psid', 128)->unique()->index();
            $table->string('first_name', 128)->nullable();
            $table->string('last_name', 128)->nullable();
            $table->timestamp('last_interaction_at')->index();
            $table->timestamps();
        });

        Schema::create('fb_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fb_conversation_id')->constrained('fb_conversations')->cascadeOnDelete();
            $table->string('fb_message_mid', 255)->unique()->index();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->text('message_text')->nullable();
            $table->json('raw_payload');
            $table->timestamp('sent_at')->index();
            $table->timestamps();
        });

        Schema::create('fb_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fb_message_id')->constrained('fb_messages')->cascadeOnDelete();
            $table->enum('attachment_type', ['image', 'video', 'audio', 'file', 'location']);
            $table->text('url')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('saved_media_id')->nullable()->constrained('media_attachments')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fb_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 255)->unique()->index();
            $table->boolean('processed')->default(false)->index();
            $table->timestamp('created_at')->index();
        });

        Schema::create('incident_intake_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fb_conversation_id')->constrained('fb_conversations')->cascadeOnDelete();
            $table->string('current_step', 64)->default('awaiting_category');
            $table->foreignId('collected_category_id')->nullable()->constrained('incident_categories')->nullOnDelete();
            $table->string('collected_phone', 32)->nullable();
            $table->text('collected_description')->nullable();
            $table->decimal('collected_latitude', 10, 8)->nullable();
            $table->decimal('collected_longitude', 11, 8)->nullable();
            $table->foreignId('collected_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->boolean('is_complete')->default(false)->index();
            $table->foreignId('generated_incident_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_intake_records');
        Schema::dropIfExists('fb_webhook_events');
        Schema::dropIfExists('fb_attachments');
        Schema::dropIfExists('fb_messages');
        Schema::dropIfExists('fb_conversations');
        Schema::dropIfExists('fb_integration_settings');
    }
};
