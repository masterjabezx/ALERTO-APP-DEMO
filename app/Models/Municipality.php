<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'province',
        'region',
        'postal_code',
        'center_latitude',
        'center_longitude',
    ];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'decimal:8',
            'center_longitude' => 'decimal:8',
        ];
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
}
