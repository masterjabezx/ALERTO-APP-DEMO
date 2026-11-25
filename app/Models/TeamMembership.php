<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'personnel_id',
        'role_in_team',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }
}
