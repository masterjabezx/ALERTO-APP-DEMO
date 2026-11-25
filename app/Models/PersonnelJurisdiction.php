<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelJurisdiction extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_id',
        'barangay_id',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
