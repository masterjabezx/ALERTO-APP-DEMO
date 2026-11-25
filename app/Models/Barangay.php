<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barangay extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'name',
        'contact_number',
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

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function boundary(): HasOne
    {
        return $this->hasOne(BarangayBoundary::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function citizenAddresses(): HasMany
    {
        return $this->hasMany(CitizenAddress::class);
    }
}
