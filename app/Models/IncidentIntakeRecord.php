<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentIntakeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'fb_conversation_id',
        'current_step',
        'status',
        'collected_category_id',
        'collected_phone',
        'collected_description',
        'collected_latitude',
        'collected_longitude',
        'collected_barangay_id',
        'location_details',
        'location_source',
        'is_complete',
        'generated_incident_id',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'collected_latitude' => 'decimal:8',
            'collected_longitude' => 'decimal:8',
            'is_complete' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(FbConversation::class, 'fb_conversation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'collected_category_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'collected_barangay_id');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'generated_incident_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
