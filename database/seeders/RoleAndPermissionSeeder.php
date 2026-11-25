<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => [
                'display_name' => 'LDRRMO SUPER ADMIN',
                'description' => 'Highest authority in the entire ALERTO APP system. Full oversight, Admin creation, system permissions, and database backup/restore.',
            ],
            'admin' => [
                'display_name' => 'DIAL DAPH COMMAND CENTER',
                'description' => 'Main emergency receiving, calling, verification, classification, and response coordination portal.',
            ],
            'office' => [
                'display_name' => 'Office',
                'description' => 'Departmental dispatch and incident triage center.',
            ],
            'personnel' => [
                'display_name' => 'Personnel',
                'description' => 'Field responders and tactical emergency units.',
            ],
            'citizen' => [
                'display_name' => 'Citizen',
                'description' => 'General public reporter and localized alert recipient.',
            ],
        ];

        $roleModels = [];
        foreach ($roles as $name => $data) {
            $roleModels[$name] = Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $data['display_name'],
                    'description' => $data['description'],
                ]
            );
        }

        $permissions = [
            // Incident permissions
            ['name' => 'incident:view_all', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin']],
            ['name' => 'incident:view_office', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'office']],
            ['name' => 'incident:view_assigned', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'office', 'personnel']],
            ['name' => 'incident:view_own', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'citizen']],
            ['name' => 'incident:create', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'citizen', 'office', 'personnel']],
            ['name' => 'incident:review', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'office']],
            ['name' => 'incident:dispatch', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'office']],
            ['name' => 'incident:update_milestone', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'personnel']],
            ['name' => 'incident:close', 'group_name' => 'incidents', 'roles' => ['super_admin', 'admin', 'office']],
            ['name' => 'incident:super_override', 'group_name' => 'incidents', 'roles' => ['super_admin']],
            // SOS permissions
            ['name' => 'sos:view_all', 'group_name' => 'sos', 'roles' => ['super_admin', 'admin']],
            ['name' => 'sos:manage', 'group_name' => 'sos', 'roles' => ['super_admin', 'admin']],
            // Map permissions
            ['name' => 'map:view_live_operations', 'group_name' => 'map', 'roles' => ['super_admin', 'admin', 'office']],
            ['name' => 'map:view_public_advisories', 'group_name' => 'map', 'roles' => ['super_admin', 'admin', 'office', 'personnel', 'citizen']],
            ['name' => 'map:telemetry_stream', 'group_name' => 'map', 'roles' => ['super_admin', 'admin', 'personnel']],
            // Messenger permissions
            ['name' => 'chat:operational_channel', 'group_name' => 'messenger', 'roles' => ['super_admin', 'admin', 'office', 'personnel']],
            ['name' => 'chat:inter_office', 'group_name' => 'messenger', 'roles' => ['super_admin', 'admin', 'office']],
            // System permissions
            ['name' => 'system:configure_routing', 'group_name' => 'system', 'roles' => ['super_admin', 'admin']],
            ['name' => 'system:manage_users', 'group_name' => 'system', 'roles' => ['super_admin', 'admin']],
            ['name' => 'system:manage_offices', 'group_name' => 'system', 'roles' => ['super_admin', 'admin']],
            ['name' => 'system:view_audit_logs', 'group_name' => 'system', 'roles' => ['super_admin', 'admin']],
            ['name' => 'system:manage_admins', 'group_name' => 'system', 'roles' => ['super_admin']],
            ['name' => 'system:manage_permissions', 'group_name' => 'system', 'roles' => ['super_admin']],
            ['name' => 'system:backups', 'group_name' => 'system', 'roles' => ['super_admin']],
            ['name' => 'hotlines:manage', 'group_name' => 'system', 'roles' => ['super_admin', 'admin']],
        ];

        $superAdminOnlyPerms = [];

        foreach ($permissions as $permData) {
            $perm = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'group_name' => $permData['group_name'],
                    'description' => 'Grants permission to '.str_replace([':', '_'], ' ', $permData['name']),
                ]
            );

            if ($permData['roles'] === ['super_admin']) {
                $superAdminOnlyPerms[] = $perm->id;
            }

            foreach ($permData['roles'] as $roleName) {
                if (isset($roleModels[$roleName])) {
                    $roleModels[$roleName]->permissions()->syncWithoutDetaching([$perm->id]);
                }
            }
        }

        if (isset($roleModels['admin']) && $superAdminOnlyPerms !== []) {
            $roleModels['admin']->permissions()->detach($superAdminOnlyPerms);
        }
    }
}
