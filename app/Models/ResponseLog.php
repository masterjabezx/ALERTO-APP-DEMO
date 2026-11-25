<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'incident_id',
        'assignment_id',
        'personnel_id',
        'milestone',
        'latitude',
        'longitude',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'recorded_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(IncidentAssignment::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }
}
