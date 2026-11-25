<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilitySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_id',
        'shift_started_at',
        'shift_ended_at',
        'starting_status',
        'ending_status',
    ];

    protected function casts(): array
    {
        return [
            'shift_started_at' => 'datetime',
            'shift_ended_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }
}
