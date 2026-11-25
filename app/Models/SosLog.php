<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sos_alert_id',
        'actor_id',
        'action_type',
        'call_outcome',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function sosAlert(): BelongsTo
    {
        return $this->belongsTo(SosAlert::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getActionAttribute(): string
    {
        return (string) $this->action_type;
    }

    public function getDetailsAttribute(): ?string
    {
        return $this->notes;
    }
}
