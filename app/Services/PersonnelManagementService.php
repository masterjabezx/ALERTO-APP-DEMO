<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PersonnelLocationUpdated;
use App\Models\AuditLog;
use App\Models\AvailabilitySession;
use App\Models\IncidentAssignment;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelLocation;
use App\Models\PersonnelProfile;
use App\Models\ResponseBase;
use App\Models\ResponseTeam;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Models\UserPresence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PersonnelManagementService
{
    /**
     * Retrieve paginated and filtered personnel directory.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredPersonnel(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = PersonnelProfile::with([
            'user.userPresence',
            'office',
            'availability',
            'teams',
            'jurisdictions',
        ]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('badge_number', 'like', "%{$search}%")
                    ->orWhere('rank_designation', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['office_id'])) {
            $query->where('office_id', (int) $filters['office_id']);
        }

        if (! empty($filters['team_id'])) {
            $query->whereHas('teams', fn ($q) => $q->where('response_teams.id', (int) $filters['team_id']));
        }

        if (! empty($filters['operational_status']) && $filters['operational_status'] !== 'all') {
            $query->whereHas('availability', fn ($q) => $q->where('operational_status', $filters['operational_status']));
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== 'all' && $filters['is_active'] !== '') {
            $isActive = (bool) $filters['is_active'];
            $query->whereHas('user', fn ($q) => $q->where('is_active', $isActive));
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Register a new personnel account and linked operational profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPersonnel(array $data, ?User $admin = null): PersonnelProfile
    {
        return DB::transaction(function () use ($data, $admin) {
            // Strict Office-scoping: Office creators can ONLY create personnel under their own office.
            $officeId = (int) $data['office_id'];
            if ($admin && $admin->isOffice()) {
                $primaryOffice = $admin->primaryOffice();
                if (! $primaryOffice || ! $admin->canManageOfficeStaffAccounts()) {
                    throw new AccessDeniedHttpException('Unauthorized to create personnel accounts.');
                }
                $officeId = $primaryOffice->id;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'],
                'password' => Hash::make($data['password']),
                'account_type' => 'personnel',
                'is_active' => true,
                'phone_verified_at' => now(),
            ]);

            $role = Role::where('name', 'personnel')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            // Ensure presence and settings
            UserPresence::create([
                'user_id' => $user->id,
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]);

            UserNotificationSetting::create([
                'user_id' => $user->id,
                'enable_push' => true,
                'enable_sms' => true,
            ]);

            $profile = PersonnelProfile::create([
                'user_id' => $user->id,
                'office_id' => $officeId,
                'badge_number' => $data['badge_number'],
                'username' => $data['username'] ?? null,
                'rank_designation' => $data['rank_designation'],
                'specialization' => $data['specialization'] ?? null,
                'is_team_leader' => (bool) ($data['is_team_leader'] ?? false),
                'authorized_categories' => $data['authorized_categories'] ?? null,
            ]);

            // Initial availability state
            PersonnelAvailability::create([
                'personnel_id' => $profile->id,
                'operational_status' => 'off_duty',
                'status_reason' => 'Account Provisioned',
                'status_updated_at' => now(),
            ]);

            // Team Assignment
            if (! empty($data['team_ids']) && is_array($data['team_ids'])) {
                foreach ($data['team_ids'] as $teamId) {
                    $profile->teams()->syncWithoutDetaching([
                        $teamId => [
                            'role_in_team' => $data['role_in_team'] ?? 'member',
                            'assigned_at' => now(),
                        ],
                    ]);
                }
            }

            // Jurisdictions Assignment
            if (! empty($data['barangay_ids']) && is_array($data['barangay_ids'])) {
                $primaryId = $data['primary_barangay_id'] ?? $data['barangay_ids'][0] ?? null;
                foreach ($data['barangay_ids'] as $bgId) {
                    $profile->jurisdictions()->syncWithoutDetaching([
                        $bgId => ['is_primary' => ((int) $bgId === (int) $primaryId)],
                    ]);
                }
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'personnel:created',
                    'auditable_type' => PersonnelProfile::class,
                    'auditable_id' => $profile->id,
                    'new_values' => ['badge_number' => $profile->badge_number, 'name' => $user->name],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $profile;
        });
    }

    /**
     * Update personnel profile and organizational assignments.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePersonnel(PersonnelProfile $profile, array $data, ?User $admin = null): PersonnelProfile
    {
        return DB::transaction(function () use ($profile, $data, $admin) {
            $user = $profile->user;

            $userData = ['name' => $data['name']];
            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }
            if (isset($data['phone_number'])) {
                $userData['phone_number'] = $data['phone_number'];
            }
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            $user->update($userData);

            $profile->update([
                'office_id' => (int) ($data['office_id'] ?? $profile->office_id),
                'badge_number' => $data['badge_number'] ?? $profile->badge_number,
                'username' => $data['username'] ?? $profile->username,
                'rank_designation' => $data['rank_designation'] ?? $profile->rank_designation,
                'specialization' => $data['specialization'] ?? $profile->specialization,
                'is_team_leader' => isset($data['is_team_leader']) ? (bool) $data['is_team_leader'] : $profile->is_team_leader,
                'authorized_categories' => $data['authorized_categories'] ?? $profile->authorized_categories,
            ]);

            // Sync Teams if provided
            if (isset($data['team_ids']) && is_array($data['team_ids'])) {
                $syncData = [];
                foreach ($data['team_ids'] as $teamId) {
                    $syncData[$teamId] = ['role_in_team' => 'member', 'assigned_at' => now()];
                }
                $profile->teams()->sync($syncData);
            }

            // Sync Jurisdictions if provided
            if (isset($data['barangay_ids']) && is_array($data['barangay_ids'])) {
                $primaryId = $data['primary_barangay_id'] ?? $data['barangay_ids'][0] ?? null;
                $syncData = [];
                foreach ($data['barangay_ids'] as $bgId) {
                    $syncData[$bgId] = ['is_primary' => ((int) $bgId === (int) $primaryId)];
                }
                $profile->jurisdictions()->sync($syncData);
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'personnel:updated',
                    'auditable_type' => PersonnelProfile::class,
                    'auditable_id' => $profile->id,
                    'new_values' => $data,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $profile;
        });
    }

    /**
     * Activate or deactivate personnel account.
     */
    public function togglePersonnelStatus(PersonnelProfile $profile, bool $isActive, ?User $admin = null): PersonnelProfile
    {
        return DB::transaction(function () use ($profile, $isActive, $admin) {
            $user = $profile->user;
            $user->is_active = $isActive;
            $user->save();

            // If deactivated, force offline availability
            if (! $isActive) {
                $this->forceOffline($profile, 'Account Deactivated');
            }

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => $isActive ? 'personnel:activated' : 'personnel:deactivated',
                    'auditable_type' => PersonnelProfile::class,
                    'auditable_id' => $profile->id,
                    'new_values' => ['is_active' => $isActive],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }

            return $profile;
        });
    }

    /**
     * Reset personnel password securely.
     */
    public function resetPersonnelPassword(PersonnelProfile $profile, string $newPassword, ?User $admin = null): void
    {
        DB::transaction(function () use ($profile, $newPassword, $admin) {
            $user = $profile->user;
            $user->password = Hash::make($newPassword);
            $user->save();

            if ($admin) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'event_type' => 'personnel:password_reset',
                    'auditable_type' => PersonnelProfile::class,
                    'auditable_id' => $profile->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);
            }
        });
    }

    /**
     * Set personnel availability with active assignment safety check.
     *
     * @throws InvalidArgumentException
     */
    public function setPersonnelAvailability(User $user, string $status, ?string $reason = null): array
    {
        $personnel = $user->personnelProfile;
        if (! $personnel) {
            throw new InvalidArgumentException('User does not have an active personnel operational profile.');
        }

        $mappedStatus = match ($status) {
            'online', 'available' => 'available',
            'offline', 'off_duty' => 'off_duty',
            'busy' => 'busy',
            'on_scene' => 'on_scene',
            'dispatched' => 'dispatched',
            default => null,
        };

        if ($mappedStatus === null) {
            throw new InvalidArgumentException("Invalid availability status: {$status}");
        }

        // Active Assignment Protection Rule:
        // Going Offline must not abandon an active incident or erase current assignments.
        // It disables new dispatch availability while preserving all active task relationships.
        $activeAssignmentsCount = 0;
        if ($mappedStatus === 'off_duty') {
            $activeAssignmentsCount = IncidentAssignment::where('personnel_id', $personnel->id)
                ->where('status', 'active')
                ->whereHas('incident', fn ($q) => $q->whereNotIn('status', ['resolved', 'closed', 'rejected']))
                ->count();
        }

        return DB::transaction(function () use ($user, $personnel, $mappedStatus, $reason, $activeAssignmentsCount) {
            $currentAvailability = $personnel->availability;
            $oldStatus = $currentAvailability?->operational_status ?? 'off_duty';

            PersonnelAvailability::updateOrCreate(
                ['personnel_id' => $personnel->id],
                [
                    'operational_status' => $mappedStatus,
                    'status_reason' => $reason ?? ($mappedStatus === 'available' ? 'Duty Shift Started' : 'Duty Shift Ended'),
                    'status_updated_at' => now(),
                ]
            );

            // Update user presence
            UserPresence::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => $mappedStatus === 'available' ? 'online' : 'offline',
                    'last_heartbeat_at' => now(),
                ]
            );

            // Manage shift sessions
            if ($mappedStatus === 'available' && $oldStatus === 'off_duty') {
                AvailabilitySession::create([
                    'personnel_id' => $personnel->id,
                    'shift_started_at' => now(),
                    'starting_status' => 'available',
                ]);
            } elseif ($mappedStatus === 'off_duty' && $oldStatus !== 'off_duty') {
                $session = AvailabilitySession::where('personnel_id', $personnel->id)
                    ->whereNull('shift_ended_at')
                    ->latest('id')
                    ->first();

                if ($session) {
                    $session->update([
                        'shift_ended_at' => now(),
                        'ending_status' => 'off_duty',
                    ]);
                }
            }

            return [
                'operational_status' => $mappedStatus,
                'presence_status' => $mappedStatus === 'available' ? 'online' : 'offline',
                'active_assignments_count' => $activeAssignmentsCount,
                'message' => $mappedStatus === 'available'
                    ? 'Status updated to ONLINE. You are now active for emergency dispatches.'
                    : ($activeAssignmentsCount > 0
                        ? "Status updated to OFFLINE for new dispatches. Your {$activeAssignmentsCount} active assigned emergency incident(s) remain open."
                        : 'Status updated to OFFLINE. Shift concluded.'),
            ];
        });
    }

    /**
     * Force responder offline (system, admin, or session termination).
     */
    public function forceOffline(PersonnelProfile $profile, string $reason): void
    {
        PersonnelAvailability::updateOrCreate(
            ['personnel_id' => $profile->id],
            [
                'operational_status' => 'off_duty',
                'status_reason' => $reason,
                'status_updated_at' => now(),
            ]
        );

        UserPresence::updateOrCreate(
            ['user_id' => $profile->user_id],
            [
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]
        );

        $session = AvailabilitySession::where('personnel_id', $profile->id)
            ->whereNull('shift_ended_at')
            ->latest('id')
            ->first();

        if ($session) {
            $session->update([
                'shift_ended_at' => now(),
                'ending_status' => 'off_duty',
            ]);
        }
    }

    /**
     * Record heartbeat telemetry and update presence.
     */
    public function recordHeartbeat(User $user, ?string $device = null): void
    {
        UserPresence::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'online',
                'current_device' => $device ?? request()->userAgent(),
                'ip_address' => request()->ip(),
                'last_heartbeat_at' => now(),
            ]
        );
    }

    /**
     * Record duty GPS coordinates for live operations map.
     */
    public function recordPersonnelLocation(
        PersonnelProfile $profile,
        float $lat,
        float $lng,
        ?float $accuracy = null,
        ?float $speed = null,
        ?float $heading = null
    ): PersonnelLocation {
        return DB::transaction(function () use ($profile, $lat, $lng, $accuracy, $speed, $heading) {
            $location = PersonnelLocation::create([
                'personnel_id' => $profile->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'speed' => $speed,
                'heading' => $heading,
                'recorded_at' => now(),
                'created_at' => now(),
            ]);

            // Keep presence heartbeat fresh
            $this->recordHeartbeat($profile->user);

            event(new PersonnelLocationUpdated(
                $profile,
                $lat,
                $lng,
                $heading,
                $speed,
                $accuracy
            ));

            return $location;
        });
    }

    /**
     * Detect connection loss for responders whose heartbeats have stalled.
     */
    public function checkConnectionLoss(int $staleMinutes = 2): int
    {
        $staleThreshold = now()->subMinutes($staleMinutes);

        // Find personnel who are marked available/online but whose heartbeat is stale
        $staleUsers = UserPresence::where('status', 'online')
            ->where('last_heartbeat_at', '<', $staleThreshold)
            ->whereHas('user', fn ($q) => $q->where('account_type', 'personnel'))
            ->get();

        foreach ($staleUsers as $presence) {
            $presence->update(['status' => 'connection_lost']);
        }

        return $staleUsers->count();
    }

    /**
     * Real-time availability monitoring metrics from live DB.
     *
     * @return array<string, mixed>
     */
    public function getAvailabilityMonitoringStats(): array
    {
        $totalPersonnel = PersonnelProfile::count();

        $statusCounts = PersonnelAvailability::query()
            ->select('operational_status', DB::raw('count(*) as count'))
            ->groupBy('operational_status')
            ->pluck('count', 'operational_status')
            ->toArray();

        $available = $statusCounts['available'] ?? 0;
        $dispatched = $statusCounts['dispatched'] ?? 0;
        $onScene = $statusCounts['on_scene'] ?? 0;
        $busy = $statusCounts['busy'] ?? 0;
        $offDuty = $statusCounts['off_duty'] ?? 0;

        $onlinePresence = UserPresence::where('status', 'online')
            ->whereHas('user', fn ($q) => $q->where('account_type', 'personnel'))
            ->count();

        $offlinePresence = UserPresence::where('status', 'offline')
            ->whereHas('user', fn ($q) => $q->where('account_type', 'personnel'))
            ->count();

        // Stale connection count (> 2 minutes without heartbeat while on available status)
        $connectionLost = PersonnelAvailability::where('operational_status', 'available')
            ->whereHas('personnel.user.userPresence', function ($q) {
                $q->where('last_heartbeat_at', '<', now()->subMinutes(2));
            })
            ->count();

        $activeAssignments = IncidentAssignment::where('status', 'active')
            ->whereNotNull('personnel_id')
            ->count();

        return [
            'total' => $totalPersonnel,
            'online' => $onlinePresence,
            'offline' => $offlinePresence,
            'available' => $available,
            'dispatched' => $dispatched,
            'on_scene' => $onScene,
            'busy' => $busy,
            'off_duty' => $offDuty,
            'connection_lost' => $connectionLost,
            'active_assignments' => $activeAssignments,
        ];
    }

    /**
     * Response Teams Management.
     */
    public function createTeam(array $data): ResponseTeam
    {
        return ResponseTeam::create([
            'office_id' => (int) $data['office_id'],
            'team_name' => $data['team_name'],
            'call_sign' => strtoupper($data['call_sign']),
            'team_type' => $data['team_type'],
            'description' => $data['description'] ?? null,
            'response_base_id' => ! empty($data['response_base_id']) ? (int) $data['response_base_id'] : null,
            'team_leader_id' => ! empty($data['team_leader_id']) ? (int) $data['team_leader_id'] : null,
            'authorized_categories' => $data['authorized_categories'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updateTeam(ResponseTeam $team, array $data): ResponseTeam
    {
        $team->update([
            'office_id' => (int) ($data['office_id'] ?? $team->office_id),
            'team_name' => $data['team_name'] ?? $team->team_name,
            'call_sign' => isset($data['call_sign']) ? strtoupper($data['call_sign']) : $team->call_sign,
            'team_type' => $data['team_type'] ?? $team->team_type,
            'description' => $data['description'] ?? $team->description,
            'response_base_id' => ! empty($data['response_base_id']) ? (int) $data['response_base_id'] : $team->response_base_id,
            'team_leader_id' => ! empty($data['team_leader_id']) ? (int) $data['team_leader_id'] : $team->team_leader_id,
            'authorized_categories' => $data['authorized_categories'] ?? $team->authorized_categories,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $team->is_active,
        ]);

        return $team;
    }

    /**
     * Response Bases Management.
     */
    public function createBase(array $data): ResponseBase
    {
        return ResponseBase::create([
            'office_id' => (int) $data['office_id'],
            'barangay_id' => (int) $data['barangay_id'],
            'base_name' => $data['base_name'],
            'address' => $data['address'],
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updateBase(ResponseBase $base, array $data): ResponseBase
    {
        $base->update([
            'office_id' => (int) ($data['office_id'] ?? $base->office_id),
            'barangay_id' => (int) ($data['barangay_id'] ?? $base->barangay_id),
            'base_name' => $data['base_name'] ?? $base->base_name,
            'address' => $data['address'] ?? $base->address,
            'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : $base->latitude,
            'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : $base->longitude,
            'contact_phone' => $data['contact_phone'] ?? $base->contact_phone,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $base->is_active,
        ]);

        return $base;
    }
}
