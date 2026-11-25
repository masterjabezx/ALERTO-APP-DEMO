<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentRoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'barangay_id',
        'priority',
        'target_office_id',
        'fallback_office_id',
        'support_office_ids',
        'response_team_id',
        'required_qualifications',
        'required_responders_count',
        'notification_recipients',
        'escalation_minutes',
        'escalation_notes',
        'auto_dispatch',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'support_office_ids' => 'array',
            'required_qualifications' => 'array',
            'required_responders_count' => 'integer',
            'notification_recipients' => 'array',
            'escalation_minutes' => 'integer',
            'auto_dispatch' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function targetOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'target_office_id');
    }

    public function fallbackOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'fallback_office_id');
    }

    public function responseTeam(): BelongsTo
    {
        return $this->belongsTo(ResponseTeam::class, 'response_team_id');
    }
}
