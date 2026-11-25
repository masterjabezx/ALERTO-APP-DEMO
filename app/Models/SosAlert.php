<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SosAlert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'idempotency_key',
        'source_type',
        'user_id',
        'personnel_id',
        'office_id',
        'current_assignment_id',
        'position_role',
        'duty_status',
        'sender_name',
        'contact_number',
        'account_status_label',
        'latitude',
        'longitude',
        'accuracy',
        'location_captured_at',
        'location_status',
        'last_known_latitude',
        'last_known_longitude',
        'last_known_at',
        'barangay_id',
        'status',
        'call_status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'converted_incident_id',
        'escalation_notes',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy' => 'decimal:2',
            'last_known_latitude' => 'decimal:8',
            'last_known_longitude' => 'decimal:8',
            'location_captured_at' => 'datetime',
            'last_known_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'triggered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function currentAssignment(): BelongsTo
    {
        return $this->belongsTo(IncidentAssignment::class, 'current_assignment_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function convertedIncident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'converted_incident_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SosAssignment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SosLog::class);
    }

    public function headline(): string
    {
        return $this->source_type === 'personnel'
            ? 'PERSONNEL SOS — IMMEDIATE ASSISTANCE REQUIRED'
            : 'CITIZEN SOS — IMMEDIATE ASSISTANCE REQUIRED';
    }

    public function getSenderTypeAttribute(): string
    {
        return $this->source_type === 'guest_citizen' ? 'guest' : (string) $this->source_type;
    }

    public function getSenderPhoneAttribute(): ?string
    {
        return $this->contact_number;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['resolved', 'converted_to_incident', 'cancelled']);
    }
}
