<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentLocation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'incident_id',
        'latitude',
        'longitude',
        'accuracy',
        'street_address',
        'landmark',
        'location_source',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy' => 'decimal:2',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
