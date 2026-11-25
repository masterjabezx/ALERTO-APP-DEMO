<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sos_alert_id',
        'office_id',
        'team_id',
        'personnel_id',
        'assigned_by',
        'status',
        'notes',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function sosAlert(): BelongsTo
    {
        return $this->belongsTo(SosAlert::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
