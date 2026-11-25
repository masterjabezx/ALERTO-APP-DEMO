<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosRoutingRule extends Model
{
    protected $fillable = [
        'source_type',
        'office_id',
        'team_id',
        'priority_order',
        'auto_notify',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'priority_order' => 'integer',
            'auto_notify' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }
}
