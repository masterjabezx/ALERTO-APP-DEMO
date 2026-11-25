<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminInvitation;
use App\Models\AuditLog;
use App\Models\IncidentCategory;
use App\Models\IncidentRoutingRule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SosRoutingRule;
use App\Models\SystemBackupRecord;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SuperAdminAccountService
{
    protected function requireSuperAdmin(User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new AccessDeniedHttpException('Only LDRRMO Super Admin may perform this operation.');
        }
    }

    /**
     * Provision a new DIAL DAPH Command Center Admin account.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAdminAccount(User $superAdmin, array $data): User
    {
        $this->requireSuperAdmin($superAdmin);

        return DB::transaction(function () use ($superAdmin, $data): User {
            $user = User::create([
                'name' => trim((string) $data['name']),
                'email' => strtolower(trim((string) $data['email'])),
                'phone_number' => trim((string) $data['phone_number']),
                'password' => Hash::make((string) $data['password']),
                'account_type' => 'admin',
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]);

            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            }

            if (! empty($data['permission_ids']) && is_array($data['permission_ids'])) {
                $allowedPerms = $this->filterSafeAdminPermissions($data['permission_ids']);
                $user->permissions()->sync($allowedPerms);
            }

            UserPresence::create([
                'user_id' => $user->id,
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => 'super_admin:admin_created',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'new_values' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'account_type' => 'admin',
                    'is_active' => $user->is_active,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $user->load(['roles', 'permissions', 'userPresence']);
        });
    }

    /**
     * Update an existing DIAL DAPH Command Center Admin account.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAdminAccount(User $superAdmin, User $target, array $data): User
    {
        $this->requireSuperAdmin($superAdmin);

        if ($target->isSuperAdmin() && (int) $target->id !== (int) $superAdmin->id) {
            throw new AccessDeniedHttpException('Cannot modify another Super Admin account.');
        }

        return DB::transaction(function () use ($superAdmin, $target, $data): User {
            $oldValues = [
                'name' => $target->name,
                'email' => $target->email,
                'phone_number' => $target->phone_number,
                'is_active' => $target->is_active,
            ];

            $update = [
                'name' => trim((string) ($data['name'] ?? $target->name)),
                'email' => strtolower(trim((string) ($data['email'] ?? $target->email))),
                'phone_number' => trim((string) ($data['phone_number'] ?? $target->phone_number)),
            ];

            if (isset($data['is_active']) && ! $target->isSuperAdmin()) {
                $update['is_active'] = (bool) $data['is_active'];
            }

            if (! empty($data['password'])) {
                $update['password'] = Hash::make((string) $data['password']);
            }

            $target->update($update);

            if (isset($data['permission_ids']) && is_array($data['permission_ids']) && ! $target->isSuperAdmin()) {
                $target->permissions()->sync($this->filterSafeAdminPermissions($data['permission_ids']));
            }

            if (! $target->is_active) {
                UserPresence::where('user_id', $target->id)->update(['status' => 'offline']);
            }

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => 'super_admin:admin_updated',
                'auditable_type' => User::class,
                'auditable_id' => $target->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'name' => $target->name,
                    'email' => $target->email,
                    'is_active' => $target->is_active,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $target->fresh(['roles', 'permissions', 'userPresence']);
        });
    }

    /**
     * Create a single-use cryptographic setup invitation link for a new Admin account.
     *
     * @param  array<string, mixed>  $data
     * @return array{invitation: AdminInvitation, plain_token: string}
     */
    public function createAdminInvitation(User $superAdmin, array $data): array
    {
        $this->requireSuperAdmin($superAdmin);

        $plainToken = Str::random(48);
        $tokenHash = hash('sha256', $plainToken);

        $invitation = DB::transaction(function () use ($superAdmin, $data, $tokenHash): AdminInvitation {
            $invitation = AdminInvitation::create([
                'name' => trim((string) $data['name']),
                'email' => strtolower(trim((string) $data['email'])),
                'phone_number' => isset($data['phone_number']) ? trim((string) $data['phone_number']) : null,
                'invited_by' => $superAdmin->id,
                'token_hash' => $tokenHash,
                'permissions' => $data['permissions'] ?? null,
                'expires_at' => now()->addHours((int) ($data['expires_hours'] ?? 48)),
            ]);

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => 'super_admin:admin_invitation_created',
                'auditable_type' => AdminInvitation::class,
                'auditable_id' => $invitation->id,
                'new_values' => [
                    'email' => $invitation->email,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $invitation;
        });

        return [
            'invitation' => $invitation,
            'plain_token' => $plainToken,
        ];
    }

    /**
     * Accept a single-use Admin invitation link and set user-chosen password.
     */
    public function acceptAdminInvitation(string $plainToken, string $password, ?string $phoneNumber = null): User
    {
        $tokenHash = hash('sha256', $plainToken);
        $invitation = AdminInvitation::where('token_hash', $tokenHash)->first();

        if (! $invitation || ! $invitation->isValid()) {
            throw new InvalidArgumentException('This Admin invitation link is invalid, already used, or expired.');
        }

        return DB::transaction(function () use ($invitation, $password, $phoneNumber): User {
            $locked = AdminInvitation::where('id', $invitation->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isValid()) {
                throw new InvalidArgumentException('This Admin invitation link has already been used or expired.');
            }

            $user = User::create([
                'name' => $locked->name,
                'email' => $locked->email,
                'phone_number' => $phoneNumber ?: ($locked->phone_number ?: '09'.random_int(100000000, 999999999)),
                'password' => Hash::make($password),
                'account_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            }

            if (is_array($locked->permissions) && $locked->permissions !== []) {
                $permIds = Permission::whereIn('name', $locked->permissions)
                    ->whereNotIn('name', $this->superAdminExclusivePermissions())
                    ->pluck('id')
                    ->all();
                $user->permissions()->sync($permIds);
            }

            UserPresence::create([
                'user_id' => $user->id,
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]);

            $locked->update([
                'accepted_at' => now(),
                'created_user_id' => $user->id,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'admin:invitation_accepted',
                'auditable_type' => AdminInvitation::class,
                'auditable_id' => $locked->id,
                'new_values' => ['created_user_id' => $user->id],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $user;
        });
    }

    /**
     * Configure or update an Automated Incident Routing Rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertIncidentRoutingRule(User $superAdmin, array $data, ?IncidentRoutingRule $rule = null): IncidentRoutingRule
    {
        $this->requireSuperAdmin($superAdmin);

        return DB::transaction(function () use ($superAdmin, $data, $rule): IncidentRoutingRule {
            $payload = [
                'category_id' => (int) $data['category_id'],
                'barangay_id' => ! empty($data['barangay_id']) ? (int) $data['barangay_id'] : null,
                'priority' => ! empty($data['priority']) ? (string) $data['priority'] : null,
                'target_office_id' => (int) $data['target_office_id'],
                'fallback_office_id' => ! empty($data['fallback_office_id']) ? (int) $data['fallback_office_id'] : null,
                'support_office_ids' => ! empty($data['support_office_ids']) && is_array($data['support_office_ids'])
                    ? array_values(array_map('intval', $data['support_office_ids']))
                    : null,
                'response_team_id' => ! empty($data['response_team_id']) ? (int) $data['response_team_id'] : null,
                'required_qualifications' => ! empty($data['required_qualifications']) && is_array($data['required_qualifications'])
                    ? array_values(array_filter(array_map('trim', $data['required_qualifications'])))
                    : null,
                'required_responders_count' => max(1, (int) ($data['required_responders_count'] ?? 1)),
                'escalation_minutes' => ! empty($data['escalation_minutes']) ? (int) $data['escalation_minutes'] : null,
                'escalation_notes' => isset($data['escalation_notes']) ? trim((string) $data['escalation_notes']) : null,
                'auto_dispatch' => ! empty($data['auto_dispatch']),
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            if ($rule) {
                $rule->update($payload);
                $saved = $rule->fresh();
                $event = 'routing_rule:updated';
            } else {
                $saved = IncidentRoutingRule::create($payload);
                $event = 'routing_rule:created';
            }

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => $event,
                'auditable_type' => IncidentRoutingRule::class,
                'auditable_id' => $saved->id,
                'new_values' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $saved;
        });
    }

    /**
     * Configure or update an SOS Routing Rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertSosRoutingRule(User $superAdmin, array $data, ?SosRoutingRule $rule = null): SosRoutingRule
    {
        $this->requireSuperAdmin($superAdmin);

        return DB::transaction(function () use ($superAdmin, $data, $rule): SosRoutingRule {
            $payload = [
                'source_type' => (string) ($data['source_type'] ?? 'all'),
                'office_id' => (int) $data['office_id'],
                'team_id' => ! empty($data['team_id']) ? (int) $data['team_id'] : null,
                'priority_order' => max(1, (int) ($data['priority_order'] ?? 1)),
                'auto_notify' => isset($data['auto_notify']) ? (bool) $data['auto_notify'] : true,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
            ];

            $saved = $rule ? tap($rule)->update($payload) : SosRoutingRule::create($payload);

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => $rule ? 'sos_routing_rule:updated' : 'sos_routing_rule:created',
                'auditable_type' => SosRoutingRule::class,
                'auditable_id' => $saved->id,
                'new_values' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $saved->fresh();
        });
    }

    /**
     * Create or update an Incident Category for DIAL DAPH Command Center assessment.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertIncidentCategory(User $superAdmin, array $data, ?IncidentCategory $category = null): IncidentCategory
    {
        $this->requireSuperAdmin($superAdmin);

        return DB::transaction(function () use ($superAdmin, $data, $category): IncidentCategory {
            $name = trim((string) $data['name']);
            $slug = ! empty($data['slug']) ? Str::slug((string) $data['slug']) : Str::slug($name);

            $payload = [
                'name' => $name,
                'slug' => $slug,
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'default_priority' => (string) ($data['default_priority'] ?? 'high'),
                'icon' => trim((string) ($data['icon'] ?? 'exclamation-triangle')),
                'color_code' => trim((string) ($data['color_code'] ?? '#DC2626')),
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            $saved = $category ? tap($category)->update($payload) : IncidentCategory::create($payload);

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => $category ? 'incident_category:updated' : 'incident_category:created',
                'auditable_type' => IncidentCategory::class,
                'auditable_id' => $saved->id,
                'new_values' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $saved->fresh();
        });
    }

    /**
     * Create an atomic JSON snapshot backup of core ALERTO APP operational tables.
     */
    public function createDatabaseBackup(User $superAdmin, ?string $notes = null): SystemBackupRecord
    {
        $this->requireSuperAdmin($superAdmin);

        $tables = [
            'system_settings',
            'incident_categories',
            'barangays',
            'offices',
            'roles',
            'permissions',
            'users',
            'office_users',
            'personnel_profiles',
            'incident_routing_rules',
            'sos_routing_rules',
            'incidents',
            'incident_locations',
            'emergency_call_attempts',
            'emergency_assessments',
            'incident_assignments',
            'sos_alerts',
            'sos_assignments',
        ];

        $snapshot = [
            'app' => 'ALERTO APP',
            'generated_at' => now()->toIso8601String(),
            'generated_by_id' => $superAdmin->id,
            'notes' => $notes,
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $snapshot['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }
        }

        $filename = sprintf('alerto-backup-%s-%s.json', now()->format('Ymd-His'), Str::random(4));
        $relativePath = 'backups/'.$filename;
        $payload = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new InvalidArgumentException('Unable to serialize database backup payload.');
        }

        Storage::disk('local')->put($relativePath, $payload);
        $size = Storage::disk('local')->size($relativePath);

        $record = SystemBackupRecord::create([
            'backup_name' => $filename,
            'file_path' => $relativePath,
            'disk' => 'local',
            'file_size_bytes' => $size,
            'checksum_sha256' => hash('sha256', $payload),
            'created_by' => $superAdmin->id,
            'status' => 'completed',
            'notes' => $notes,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $superAdmin->id,
            'event_type' => 'system:backup_created',
            'auditable_type' => SystemBackupRecord::class,
            'auditable_id' => $record->id,
            'new_values' => ['backup_name' => $filename, 'file_size_bytes' => $size],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return $record;
    }

    /**
     * Restore a previously verified JSON backup snapshot into database non-destructively (upserts).
     *
     * @return array<string, int>
     */
    public function restoreDatabaseBackup(User $superAdmin, SystemBackupRecord $record): array
    {
        $this->requireSuperAdmin($superAdmin);

        $disk = $record->disk ?: 'local';
        if (! Storage::disk($disk)->exists($record->file_path)) {
            throw new InvalidArgumentException('Backup snapshot file is missing from storage.');
        }

        $raw = (string) Storage::disk($disk)->get($record->file_path);
        if ($record->checksum_sha256 && hash('sha256', $raw) !== $record->checksum_sha256) {
            throw new InvalidArgumentException('Backup checksum verification failed. Restore aborted to protect data integrity.');
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['tables']) || ! is_array($decoded['tables'])) {
            throw new InvalidArgumentException('Invalid backup archive structure.');
        }

        $restoredCounts = [];

        DB::transaction(function () use ($superAdmin, $record, $decoded, &$restoredCounts): void {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                foreach ($decoded['tables'] as $table => $rows) {
                    if (! is_string($table) || ! is_array($rows) || ! DB::getSchemaBuilder()->hasTable($table)) {
                        continue;
                    }

                    $count = 0;
                    foreach ($rows as $row) {
                        if (! is_array($row) || ! isset($row['id'])) {
                            continue;
                        }

                        DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                        $count++;
                    }
                    $restoredCounts[$table] = $count;
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            AuditLog::create([
                'user_id' => $superAdmin->id,
                'event_type' => 'system:backup_restored',
                'auditable_type' => SystemBackupRecord::class,
                'auditable_id' => $record->id,
                'new_values' => ['backup_name' => $record->backup_name, 'restored_counts' => $restoredCounts],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        });

        return $restoredCounts;
    }

    /**
     * @param  array<mixed>  $permissionIds
     * @return array<int>
     */
    protected function filterSafeAdminPermissions(array $permissionIds): array
    {
        $excludedIds = Permission::whereIn('name', $this->superAdminExclusivePermissions())->pluck('id')->all();

        return collect($permissionIds)
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->reject(fn (int $id): bool => in_array($id, $excludedIds, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string>
     */
    protected function superAdminExclusivePermissions(): array
    {
        return [
            'system:manage_admins',
            'system:manage_permissions',
            'system:backups',
            'incident:super_override',
        ];
    }
}
