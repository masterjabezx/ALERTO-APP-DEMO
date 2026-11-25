<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyCallAttempt extends Model
{
    protected $fillable = [
        'incident_id',
        'initiated_by',
        'phone_number_dialed',
        'call_outcome',
        'verification_notes',
        'location_confirmed',
        'category_confirmed',
        'callback_required',
        'initiated_at',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'location_confirmed' => 'boolean',
            'category_confirmed' => 'boolean',
            'callback_required' => 'boolean',
            'initiated_at' => 'datetime',
            'recorded_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
