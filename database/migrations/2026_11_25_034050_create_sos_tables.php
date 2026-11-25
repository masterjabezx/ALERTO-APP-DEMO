<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 32)->unique()->index();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->enum('source_type', ['citizen', 'guest_citizen', 'personnel'])->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel_profiles')->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('current_assignment_id')->nullable()->constrained('incident_assignments')->nullOnDelete();
            $table->string('position_role', 128)->nullable();
            $table->string('duty_status', 32)->nullable();
            $table->string('sender_name', 128);
            $table->string('contact_number', 32)->index();
            $table->string('account_status_label', 64);

            $table->decimal('latitude', 10, 8)->nullable()->index();
            $table->decimal('longitude', 11, 8)->nullable()->index();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('location_captured_at')->nullable();
            $table->enum('location_status', ['live', 'manual_pin', 'last_known_stale', 'unavailable'])->default('live')->index();
            $table->decimal('last_known_latitude', 10, 8)->nullable();
            $table->decimal('last_known_longitude', 11, 8)->nullable();
            $table->timestamp('last_known_at')->nullable();
            $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();

            $table->enum('status', [
                'active',
                'acknowledged',
                'responding',
                'escalated',
                'resolved',
                'converted_to_incident',
                'cancelled',
            ])->default('active')->index();

            $table->enum('call_status', [
                'not_called',
                'answered',
                'no_answer',
                'busy',
                'invalid_number',
                'callback_required',
            ])->default('not_called')->index();

            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('converted_incident_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->text('escalation_notes')->nullable();
            $table->timestamp('triggered_at')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sos_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['all', 'citizen', 'personnel'])->default('all')->index();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('response_teams')->nullOnDelete();
            $table->unsignedSmallInteger('priority_order')->default(1);
            $table->boolean('auto_notify')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sos_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sos_alert_id')->constrained('sos_alerts')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('response_teams')->nullOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel_profiles')->nullOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['assigned', 'acknowledged', 'en_route', 'on_scene', 'completed'])->default('assigned')->index();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->index();
        });

        Schema::create('sos_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sos_alert_id')->constrained('sos_alerts')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action_type', [
                'created',
                'call_attempt',
                'acknowledged',
                'assigned',
                'escalated',
                'resolved',
                'converted',
            ])->index();
            $table->string('call_outcome', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_logs');
        Schema::dropIfExists('sos_assignments');
        Schema::dropIfExists('sos_routing_rules');
        Schema::dropIfExists('sos_alerts');
    }
};
