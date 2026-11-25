<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\Office;
use App\Models\OfficeLocation;
use App\Models\OfficeResponsibility;
use App\Models\OfficeUser;
use App\Models\PersonnelProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OfficeManagementService
{
    /**
     * Get filtered offices list with pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredOffices(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Office::with(['location.barangay', 'users', 'teams', 'responsibilities', 'authorizedBarangays'])
            ->withCount(['users', 'teams', 'personnel']);

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['department_type'])) {
            $query->where('department_type', $filters['department_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Summary metrics for Admin Office Management dashboard.
     *
     * @return array<string, int>
     */
    public function getOfficeManagementStats(): array
    {
        return [
            'total_offices' => Office::count(),
            'active_offices' => Office::where('is_active', true)->count(),
            'office_admins_count' => OfficeUser::where('office_level', 'office_admin')->count(),
            'office_staff_count' => OfficeUser::where('office_level', 'office_staff')->count(),
            'online_office_users' => UserPresence::where('status', 'online')
                ->whereHas('user', fn ($q) => $q->where('account_type', 'office'))
                ->count(),
        ];
    }

    /**
     * Register a new office with physical location and responsibilities.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOffice(array $data, ?User $admin = null): Office
    {
        return DB::transaction(function () use ($data, $admin) {
            $office = Office::create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'department_type' => $data['department_type'],
                'description' => $data['description'] ?? null,
                'response_responsibilities_summary' => $data['response_responsibilities_summary'] ?? null,
                'head_name' => $data['head_name'] ?? null,
                'head_title' => $data['head_title'] ?? null,
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'] ?? null,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);

            // Create office location
            OfficeLocation::create([
                'office_id' => $office->id,
                'barangay_id' => (int) $data['barangay_id'],
                'street_address' => $data['street_address'] ?? 'Poblacion',
                'latitude' => (float) ($data['latitude'] ?? 9.4975),
                'longitude' => (float) ($data['longitude'] ?? 122.5850),
                'service_radius_meters' => (int) ($data['service_radius_meters'] ?? 5000),
            ]);

            // Assign responsibilities
            if (! empty($data['categories']) && is_array($data['categories'])) {
                foreach ($data['categories'] as $categorySlug) {
                    OfficeResponsibility::create([
                        'office_id' => $office->id,
                        'category_slug' => $categorySlug,
                    ]);
                }
            }

            if (! empty($data['authorized_barangay_ids']) && is_array($data['authorized_barangay_ids'])) {
                $office->authorizedBarangays()->sync(array_map('intval', $data['authorized_barangay_ids']));
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'office:created',
                    'auditable_type' => Office::class,
                    'auditable_id' => $office->id,
                    'new_values' => ['name' => $office->name, 'code' => $office->code],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $office;
        });
    }

    /**
     * Update existing office attributes and location.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOffice(Office $office, array $data, ?User $admin = null): Office
    {
        return DB::transaction(function () use ($office, $data, $admin) {
            $office->update([
                'name' => $data['name'] ?? $office->name,
                'code' => isset($data['code']) ? strtoupper($data['code']) : $office->code,
                'department_type' => $data['department_type'] ?? $office->department_type,
                'description' => $data['description'] ?? $office->description,
                'response_responsibilities_summary' => array_key_exists('response_responsibilities_summary', $data) ? $data['response_responsibilities_summary'] : $office->response_responsibilities_summary,
                'head_name' => array_key_exists('head_name', $data) ? $data['head_name'] : $office->head_name,
                'head_title' => array_key_exists('head_title', $data) ? $data['head_title'] : $office->head_title,
                'contact_phone' => $data['contact_phone'] ?? $office->contact_phone,
                'contact_email' => $data['contact_email'] ?? $office->contact_email,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $office->is_active,
            ]);

            if (isset($data['barangay_id'])) {
                OfficeLocation::updateOrCreate(
                    ['office_id' => $office->id],
                    [
                        'barangay_id' => (int) $data['barangay_id'],
                        'street_address' => $data['street_address'] ?? 'Poblacion',
                        'latitude' => (float) ($data['latitude'] ?? 9.4975),
                        'longitude' => (float) ($data['longitude'] ?? 122.5850),
                        'service_radius_meters' => (int) ($data['service_radius_meters'] ?? 5000),
                    ]
                );
            }

            if (isset($data['categories']) && is_array($data['categories'])) {
                OfficeResponsibility::where('office_id', $office->id)->delete();
                foreach ($data['categories'] as $categorySlug) {
                    OfficeResponsibility::create([
                        'office_id' => $office->id,
                        'category_slug' => $categorySlug,
                    ]);
                }
            }

            if (array_key_exists('authorized_barangay_ids', $data) && is_array($data['authorized_barangay_ids'])) {
                $office->authorizedBarangays()->sync(array_map('intval', $data['authorized_barangay_ids']));
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'office:updated',
                    'auditable_type' => Office::class,
                    'auditable_id' => $office->id,
                    'new_values' => ['name' => $office->name, 'code' => $office->code],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $office;
        });
    }

    /**
     * Activate or deactivate an entire office agency.
     */
    public function toggleOfficeStatus(Office $office, bool $isActive, ?User $admin = null): Office
    {
        return DB::transaction(function () use ($office, $isActive, $admin) {
            $office->is_active = $isActive;
            $office->save();

            // Deactivate associated office users if office is disabled
            if (! $isActive) {
                foreach ($office->officeUsers as $ou) {
                    $u = $ou->user;
                    if ($u) {
                        $u->is_active = false;
                        $u->save();
                        UserPresence::where('user_id', $u->id)->update(['status' => 'offline']);
                    }
                }
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => $isActive ? 'office:activated' : 'office:deactivated',
                    'auditable_type' => Office::class,
                    'auditable_id' => $office->id,
                    'new_values' => ['is_active' => $isActive],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $office;
        });
    }

    /**
     * Create an office user (Office Admin or Office Staff) with permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOfficeUser(Office $office, array $data, ?User $admin = null): User
    {
        return DB::transaction(function () use ($office, $data, $admin) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone_number' => $data['phone_number'],
                'password' => Hash::make($data['password']),
                'account_type' => 'office',
                'is_active' => true,
            ]);

            $role = Role::where('name', 'office')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            $officeLevel = in_array($data['office_level'], ['office_admin', 'office_staff'], true)
                ? $data['office_level']
                : 'office_staff';

            // Office Admin can default to reviewing incidents if permitted
            $canReview = isset($data['can_review_incidents'])
                ? (bool) $data['can_review_incidents']
                : ($officeLevel === 'office_admin');

            $canDispatch = isset($data['can_dispatch_units'])
                ? (bool) $data['can_dispatch_units']
                : ($officeLevel === 'office_admin');

            $canManageAccounts = isset($data['can_manage_accounts'])
                ? (bool) $data['can_manage_accounts']
                : false;

            // Only Admin/Super Admin can grant can_manage_accounts or office_admin elevation
            if ($admin && $admin->isOffice()) {
                $officeLevel = 'office_staff';
                $canManageAccounts = false;
            }

            OfficeUser::create([
                'office_id' => $office->id,
                'user_id' => $user->id,
                'office_level' => $officeLevel,
                'can_review_incidents' => $canReview,
                'can_dispatch_units' => $canDispatch,
                'can_manage_accounts' => $canManageAccounts,
            ]);

            UserPresence::create([
                'user_id' => $user->id,
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]);

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'office_user:created',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'new_values' => [
                        'office_id' => $office->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'office_level' => $officeLevel,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $user;
        });
    }

    /**
     * Update office user account and permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOfficeUser(OfficeUser $officeUser, array $data, ?User $admin = null): OfficeUser
    {
        return DB::transaction(function () use ($officeUser, $data, $admin) {
            $user = $officeUser->user;
            $user->name = $data['name'] ?? $user->name;
            $user->email = isset($data['email']) ? strtolower($data['email']) : $user->email;
            $user->phone_number = $data['phone_number'] ?? $user->phone_number;

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            $officeUser->office_level = $data['office_level'] ?? $officeUser->office_level;
            $officeUser->can_review_incidents = isset($data['can_review_incidents'])
                ? (bool) $data['can_review_incidents']
                : $officeUser->can_review_incidents;
            $officeUser->can_dispatch_units = isset($data['can_dispatch_units'])
                ? (bool) $data['can_dispatch_units']
                : $officeUser->can_dispatch_units;
            if (isset($data['can_manage_accounts'])) {
                $officeUser->can_manage_accounts = (bool) $data['can_manage_accounts'];
            }
            $officeUser->save();

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'office_user:updated',
                    'auditable_type' => OfficeUser::class,
                    'auditable_id' => $officeUser->id,
                    'new_values' => [
                        'office_level' => $officeUser->office_level,
                        'can_review_incidents' => $officeUser->can_review_incidents,
                        'can_dispatch_units' => $officeUser->can_dispatch_units,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $officeUser;
        });
    }

    /**
     * Toggle active status of an office account.
     */
    public function toggleOfficeUserStatus(OfficeUser $officeUser, bool $isActive, ?User $admin = null): OfficeUser
    {
        return DB::transaction(function () use ($officeUser, $isActive, $admin) {
            $user = $officeUser->user;
            $user->is_active = $isActive;
            $user->save();

            if (! $isActive) {
                UserPresence::where('user_id', $user->id)->update(['status' => 'offline']);
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => $isActive ? 'office_user:activated' : 'office_user:deactivated',
                    'auditable_type' => OfficeUser::class,
                    'auditable_id' => $officeUser->id,
                    'new_values' => ['is_active' => $isActive],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $officeUser;
        });
    }

    /**
     * Reset password of an office user securely.
     */
    public function resetOfficeUserPassword(OfficeUser $officeUser, string $newPassword, ?User $admin = null): void
    {
        DB::transaction(function () use ($officeUser, $newPassword, $admin) {
            $user = $officeUser->user;
            $user->password = Hash::make($newPassword);
            $user->save();

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'office_user:password_reset',
                    'auditable_type' => OfficeUser::class,
                    'auditable_id' => $officeUser->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }
        });
    }

    /**
     * Scoped real-time metrics for Office Portal dashboard.
     *
     * @return array<string, int>
     */
    public function getOfficeDashboardStats(Office $office): array
    {
        $teamIds = $office->teams()->pluck('id');
        $officeResponsibilities = $office->responsibilities()->pluck('category_slug');

        // Incidents relevant to this office: assigned directly, assigned to its teams, or category responsibility
        $baseIncidentQuery = Incident::where(function (Builder $q) use ($office, $teamIds, $officeResponsibilities) {
            $q->whereHas('assignments', function (Builder $aq) use ($office, $teamIds) {
                $aq->where('office_id', $office->id)
                    ->orWhereIn('team_id', $teamIds);
            })->orWhere(function (Builder $rq) use ($office, $officeResponsibilities) {
                $rq->whereHas('category', fn ($cq) => $cq->whereIn('slug', $officeResponsibilities))
                    ->orWhereHas('category.routingRules', fn ($rrq) => $rrq->where('target_office_id', $office->id));
            });
        });

        $totalAuthorized = (clone $baseIncidentQuery)->count();

        $pendingReview = Incident::where('status', 'pending_review')
            ->where(function (Builder $q) use ($office, $officeResponsibilities) {
                $q->whereHas('category', fn ($cq) => $cq->whereIn('slug', $officeResponsibilities))
                    ->orWhereHas('category.routingRules', fn ($rrq) => $rrq->where('target_office_id', $office->id));
            })->count();

        $accepted = (clone $baseIncidentQuery)->where('status', 'accepted')->count();

        $assigned = (clone $baseIncidentQuery)->whereIn('status', ['accepted', 'dispatched', 'acknowledged'])->count();

        $dispatched = (clone $baseIncidentQuery)->where('status', 'dispatched')->count();

        $activeResponses = (clone $baseIncidentQuery)->whereIn('status', [
            'acknowledged',
            'on_the_way',
            'on_scene',
            'in_progress',
        ])->count();

        $resolved = (clone $baseIncidentQuery)->whereIn('status', ['resolved', 'closed'])->count();

        $onlinePersonnel = PersonnelProfile::where('office_id', $office->id)
            ->whereHas('user.userPresence', fn ($q) => $q->where('status', 'online'))
            ->count();

        $totalPersonnel = PersonnelProfile::where('office_id', $office->id)->count();

        return [
            'total_authorized_incidents' => $totalAuthorized,
            'pending_review' => $pendingReview,
            'accepted' => $accepted,
            'assigned' => $assigned,
            'dispatched' => $dispatched,
            'active_responses' => $activeResponses,
            'resolved' => $resolved,
            'online_personnel' => $onlinePersonnel,
            'total_personnel' => $totalPersonnel,
            'response_teams_count' => $teamIds->count(),
        ];
    }

    /**
     * Get incidents scoped exclusively to this office.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getOfficeIncidents(Office $office, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $teamIds = $office->teams()->pluck('id');
        $officeResponsibilities = $office->responsibilities()->pluck('category_slug');

        $query = Incident::with([
            'category',
            'barangay',
            'location',
            'activeAssignments.team',
            'activeAssignments.personnel.user',
            'statusHistories.changedBy',
        ])
            ->where(function (Builder $q) use ($office, $teamIds, $officeResponsibilities) {
                $q->whereHas('assignments', function (Builder $aq) use ($office, $teamIds) {
                    $aq->where('office_id', $office->id)
                        ->orWhereIn('team_id', $teamIds);
                })->orWhere(function (Builder $rq) use ($office, $officeResponsibilities) {
                    $rq->whereHas('category', fn ($cq) => $cq->whereIn('slug', $officeResponsibilities))
                        ->orWhereHas('category.routingRules', fn ($rrq) => $rrq->where('target_office_id', $office->id));
                });
            });

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('caller_name', 'like', "%{$search}%")
                    ->orWhere('caller_phone', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['barangay_id'])) {
            $query->where('barangay_id', (int) $filters['barangay_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->latest('submitted_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Get GIS spatial data for Office Incident Map.
     *
     * @return array<string, mixed>
     */
    public function getOfficeMapData(Office $office): array
    {
        $teamIds = $office->teams()->pluck('id');
        $officeResponsibilities = $office->responsibilities()->pluck('category_slug');

        $activeIncidents = Incident::with(['category', 'barangay', 'location'])
            ->whereIn('status', ['accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress'])
            ->where(function (Builder $q) use ($office, $teamIds, $officeResponsibilities) {
                $q->whereHas('assignments', function (Builder $aq) use ($office, $teamIds) {
                    $aq->where('office_id', $office->id)
                        ->orWhereIn('team_id', $teamIds);
                })->orWhere(function (Builder $rq) use ($office, $officeResponsibilities) {
                    $rq->whereHas('category', fn ($cq) => $cq->whereIn('slug', $officeResponsibilities))
                        ->orWhereHas('category.routingRules', fn ($rrq) => $rrq->where('target_office_id', $office->id));
                });
            })
            ->get();

        $activePersonnel = PersonnelProfile::with(['user.userPresence', 'teams'])
            ->where('office_id', $office->id)
            ->whereHas('user.userPresence', fn ($q) => $q->where('status', 'online'))
            ->get();

        $teams = $office->teams()->with(['base', 'members.user'])->get();

        return [
            'office' => $office,
            'incidents' => $activeIncidents,
            'personnel' => $activePersonnel,
            'teams' => $teams,
        ];
    }

    /**
     * Get report dataset for Office Records & Reports.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getOfficeReportsData(Office $office, array $filters): Collection
    {
        $teamIds = $office->teams()->pluck('id');
        $officeResponsibilities = $office->responsibilities()->pluck('category_slug');

        $query = Incident::with(['category', 'barangay', 'assignments.team', 'assignments.personnel.user'])
            ->where(function (Builder $q) use ($office, $teamIds, $officeResponsibilities) {
                $q->whereHas('assignments', function (Builder $aq) use ($office, $teamIds) {
                    $aq->where('office_id', $office->id)
                        ->orWhereIn('team_id', $teamIds);
                })->orWhere(function (Builder $rq) use ($office, $officeResponsibilities) {
                    $rq->whereHas('category', fn ($cq) => $cq->whereIn('slug', $officeResponsibilities))
                        ->orWhereHas('category.routingRules', fn ($rrq) => $rrq->where('target_office_id', $office->id));
                });
            });

        if (! empty($filters['timeframe'])) {
            match ($filters['timeframe']) {
                'daily' => $query->whereDate('submitted_at', today()),
                'weekly' => $query->where('submitted_at', '>=', now()->subDays(7)),
                'monthly' => $query->where('submitted_at', '>=', now()->subDays(30)),
                'annual' => $query->where('submitted_at', '>=', now()->subYear()),
                default => null,
            };
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('submitted_at')->get();
    }
}
