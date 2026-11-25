<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalityBoundary extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'geojson_boundary',
        'bounding_min_lat',
        'bounding_max_lat',
        'bounding_min_lng',
        'bounding_max_lng',
        'source_information',
        'is_verified',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'bounding_min_lat' => 'decimal:8',
            'bounding_max_lat' => 'decimal:8',
            'bounding_min_lng' => 'decimal:8',
            'bounding_max_lng' => 'decimal:8',
            'is_verified' => 'boolean',
            'imported_at' => 'datetime',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
