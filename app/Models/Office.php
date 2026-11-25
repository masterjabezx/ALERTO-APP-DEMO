<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'department_type',
        'description',
        'response_responsibilities_summary',
        'contact_phone',
        'contact_email',
        'head_name',
        'head_title',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function officeUsers(): HasMany
    {
        return $this->hasMany(OfficeUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'office_users')
            ->withPivot(['office_level', 'can_review_incidents', 'can_dispatch_units', 'can_manage_accounts'])
            ->withTimestamps();
    }

    public function authorizedBarangays(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'office_authorized_barangays')
            ->withTimestamps();
    }

    public function location(): HasOne
    {
        return $this->hasOne(OfficeLocation::class);
    }

    public function responsibilities(): HasMany
    {
        return $this->hasMany(OfficeResponsibility::class);
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(PersonnelProfile::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ResponseTeam::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }
}
