<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamJurisdiction extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'barangay_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }
}
