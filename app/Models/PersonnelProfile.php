<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PersonnelProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'office_id',
        'badge_number',
        'username',
        'rank_designation',
        'specialization',
        'is_team_leader',
        'profile_photo_path',
        'authorized_categories',
    ];

    protected function casts(): array
    {
        return [
            'is_team_leader' => 'boolean',
            'authorized_categories' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function availability(): HasOne
    {
        return $this->hasOne(PersonnelAvailability::class, 'personnel_id');
    }

    public function availabilitySessions(): HasMany
    {
        return $this->hasMany(AvailabilitySession::class, 'personnel_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(ResponseTeam::class, 'team_memberships', 'personnel_id', 'team_id')
            ->withPivot(['role_in_team', 'assigned_at'])
            ->withTimestamps();
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class, 'personnel_id');
    }

    public function jurisdictions(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'personnel_jurisdictions', 'personnel_id', 'barangay_id')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(PersonnelLocation::class, 'personnel_id');
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(PersonnelLocation::class, 'personnel_id')->latestOfMany('recorded_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'personnel_id');
    }

    public function responseLogs(): HasMany
    {
        return $this->hasMany(ResponseLog::class, 'personnel_id');
    }
}
