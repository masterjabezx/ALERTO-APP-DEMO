<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'idempotency_key',
        'client_uuid',
        'reporter_id',
        'is_guest_report',
        'guest_claim_token_hash',
        'guest_claimed_at',
        'source_channel',
        'category_id',
        'priority',
        'status',
        'workflow_stage',
        'assessment_status',
        'processed_by_admin_id',
        'locked_for_admin_at',
        'description',
        'caller_name',
        'caller_phone',
        'is_mobile_number_verified',
        'contact_verification_status',
        'barangay_id',
        'submitted_at',
        'accepted_at',
        'classified_at',
        'dispatched_at',
        'resolved_at',
        'closed_at',
        'overridden_by_super_admin_id',
        'version_lock',
        'has_assignment_warning',
        'assignment_warning_message',
    ];

    protected function casts(): array
    {
        return [
            'is_guest_report' => 'boolean',
            'is_mobile_number_verified' => 'boolean',
            'has_assignment_warning' => 'boolean',
            'guest_claimed_at' => 'datetime',
            'locked_for_admin_at' => 'datetime',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'classified_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'version_lock' => 'integer',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function processedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_admin_id');
    }

    public function overriddenBySuperAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by_super_admin_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function location(): HasOne
    {
        return $this->hasOne(IncidentLocation::class);
    }

    public function callAttempts(): HasMany
    {
        return $this->hasMany(EmergencyCallAttempt::class)->latest('initiated_at');
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(EmergencyAssessment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(IncidentReview::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(IncidentAssignment::class)->where('status', 'active');
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class)->where('status', 'active');
    }

    public function contactVerifications(): HasMany
    {
        return $this->hasMany(IncidentContactVerification::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(IncidentStatusHistory::class);
    }

    public function responseLogs(): HasMany
    {
        return $this->hasMany(ResponseLog::class);
    }

    public function evidenceRecords(): HasMany
    {
        return $this->hasMany(IncidentEvidenceRecord::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(IncidentEvidenceRecord::class);
    }

    public function mediaAttachments(): HasMany
    {
        return $this->hasMany(MediaAttachment::class, 'incident_id');
    }

    public function hasLockedEvidence(): bool
    {
        return ! in_array($this->status, ['submitted', 'pending_review'], true);
    }

    public function isUnclassifiedReport(): bool
    {
        return $this->category_id === null || in_array($this->workflow_stage, ['new_report', 'awaiting_call', 'assessment_in_progress'], true);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function sosAlerts(): HasMany
    {
        return $this->hasMany(SosAlert::class, 'converted_incident_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['resolved', 'closed', 'rejected']);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeIncomingReports(Builder $query): Builder
    {
        return $query->whereIn('workflow_stage', ['new_report', 'awaiting_call', 'assessment_in_progress'])
            ->where('status', 'pending_review');
    }

    public function scopeDispatched(Builder $query): Builder
    {
        return $query->where('status', 'dispatched');
    }
}
