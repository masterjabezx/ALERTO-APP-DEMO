<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponseTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'team_name',
        'call_sign',
        'team_type',
        'description',
        'response_base_id',
        'team_leader_id',
        'authorized_categories',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'authorized_categories' => 'array',
        ];
    }

    public function base(): BelongsTo
    {
        return $this->belongsTo(ResponseBase::class, 'response_base_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'team_leader_id');
    }

    public function jurisdictions(): HasMany
    {
        return $this->hasMany(TeamJurisdiction::class, 'team_id');
    }

    public function barangays(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'team_jurisdictions', 'team_id', 'barangay_id')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(PersonnelProfile::class, 'team_memberships', 'team_id', 'personnel_id')
            ->withPivot(['role_in_team', 'assigned_at'])
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'team_id');
    }
}
