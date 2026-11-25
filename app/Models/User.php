<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'phone_verified_at',
        'email_verified_at',
        'password',
        'account_type',
        'is_active',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function citizenProfile(): HasOne
    {
        return $this->hasOne(CitizenProfile::class);
    }

    public function citizenAddresses(): HasMany
    {
        return $this->hasMany(CitizenAddress::class);
    }

    public function personnelProfile(): HasOne
    {
        return $this->hasOne(PersonnelProfile::class);
    }

    public function personnelAvailability(): HasOneThrough
    {
        return $this->hasOneThrough(PersonnelAvailability::class, PersonnelProfile::class, 'user_id', 'personnel_id');
    }

    public function officeUsers(): HasMany
    {
        return $this->hasMany(OfficeUser::class);
    }

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'office_users')
            ->withPivot(['office_level', 'can_review_incidents', 'can_dispatch_units'])
            ->withTimestamps();
    }

    public function primaryOffice(): ?Office
    {
        return $this->offices()->first();
    }

    public function userPresence(): HasOne
    {
        return $this->hasOne(UserPresence::class);
    }

    public function presence(): HasOne
    {
        return $this->userPresence();
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reporter_id');
    }

    public function reviewedIncidents(): HasMany
    {
        return $this->hasMany(IncidentReview::class, 'reviewed_by');
    }

    public function assignedDispatches(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'assigned_by');
    }

    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(MediaAttachment::class, 'uploader_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['is_admin', 'last_read_message_id', 'joined_at']);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function sosAlerts(): HasMany
    {
        return $this->hasMany(SosAlert::class, 'user_id');
    }

    public function processedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'processed_by_admin_id');
    }

    public function overriddenIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'overridden_by_super_admin_id');
    }

    public function isCitizen(): bool
    {
        return $this->account_type === 'citizen';
    }

    public function isPersonnel(): bool
    {
        return $this->account_type === 'personnel';
    }

    public function isOffice(): bool
    {
        return $this->account_type === 'office';
    }

    public function isSuperAdmin(): bool
    {
        return $this->account_type === 'super_admin';
    }

    public function isCommandCenterAdmin(): bool
    {
        return $this->account_type === 'admin';
    }

    /**
     * Operational Command Center authority (includes both DIAL DAPH Admin and LDRRMO Super Admin).
     * Use isSuperAdmin() explicitly whenever checking Super Admin exclusive privileges.
     */
    public function isAdmin(): bool
    {
        return in_array($this->account_type, ['admin', 'super_admin'], true);
    }

    public function isOfficeAdmin(): bool
    {
        return $this->isOffice() && $this->officeUsers()->where('office_level', 'office_admin')->exists();
    }

    public function isOfficeStaff(): bool
    {
        return $this->isOffice() && $this->officeUsers()->where('office_level', 'office_staff')->exists();
    }

    public function canManageOfficeStaffAccounts(?int $officeId = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isOffice()) {
            return false;
        }

        return $this->officeUsers()
            ->when($officeId !== null, fn (Builder $q) => $q->where('office_id', $officeId))
            ->where('office_level', 'office_admin')
            ->where('can_manage_accounts', true)
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        if ($this->roles->isNotEmpty()) {
            return $this->roles->contains('name', $role);
        }

        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $superAdminExclusive = [
            'system:manage_admins',
            'system:manage_permissions',
            'system:backups',
            'incident:super_override',
        ];

        if ($this->isCommandCenterAdmin()) {
            if (in_array($permission, $superAdminExclusive, true)) {
                return false;
            }

            if ($this->permissions()->where('name', $permission)->exists()) {
                return true;
            }

            return $this->roles()->whereHas('permissions', fn ($q) => $q->where('name', $permission))->exists()
                || ! in_array($permission, $superAdminExclusive, true);
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', fn ($q) => $q->where('name', $permission))->exists();
    }

    public function canReviewIncident(Incident $incident): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isOffice()) {
            return false;
        }

        // Office Admin or users with explicit can_review_incidents flag
        $authorizedOffices = $this->officeUsers()
            ->where('can_review_incidents', true)
            ->pluck('office_id');

        if ($authorizedOffices->isEmpty()) {
            return false;
        }

        // Check if incident is routed or assigned or belongs to this office responsibility
        $routingRule = IncidentRoutingRule::where('category_id', $incident->category_id)->first();
        if ($routingRule && $authorizedOffices->contains($routingRule->target_office_id)) {
            return true;
        }

        if ($incident->category) {
            $hasResponsibility = OfficeResponsibility::whereIn('office_id', $authorizedOffices)
                ->where('category_slug', $incident->category->slug)
                ->exists();
            if ($hasResponsibility) {
                return true;
            }
        }

        return $incident->assignments()->whereIn('office_id', $authorizedOffices)->exists();
    }

    public function scopeCitizens(Builder $query): Builder
    {
        return $query->where('account_type', 'citizen');
    }

    public function scopePersonnel(Builder $query): Builder
    {
        return $query->where('account_type', 'personnel');
    }

    public function scopeOffices(Builder $query): Builder
    {
        return $query->where('account_type', 'office');
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('account_type', 'admin');
    }

    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->where('account_type', 'super_admin');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
