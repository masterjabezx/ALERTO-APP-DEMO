<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand users.account_type enum cleanly to the 5-tier hierarchy
        DB::statement("ALTER TABLE users MODIFY COLUMN account_type ENUM('super_admin', 'admin', 'office', 'personnel', 'citizen') NOT NULL DEFAULT 'citizen'");

        // 2. Ensure roles exist cleanly without touching existing rows unnecessarily
        $now = now();
        $superAdminRoleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        if (! $superAdminRoleId) {
            $superAdminRoleId = DB::table('roles')->insertGetId([
                'name' => 'super_admin',
                'display_name' => 'LDRRMO Super Admin',
                'description' => 'Highest authority in ALERTO APP for the Municipality of Hinoba-an. Full oversight, Admin account management, permissions, routing configuration, and backup control.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        if ($adminRoleId) {
            DB::table('roles')->where('id', $adminRoleId)->update([
                'display_name' => 'DIAL DAPH Command Center Admin',
                'description' => 'Emergency receiving, citizen callback verification, incident classification, assessment, and automated dispatch coordination.',
                'updated_at' => $now,
            ]);
        } else {
            $adminRoleId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'display_name' => 'DIAL DAPH Command Center Admin',
                'description' => 'Emergency receiving, citizen callback verification, incident classification, assessment, and automated dispatch coordination.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Safely convert existing Admin accounts to LDRRMO Super Admin while preserving IDs, credentials, and historical relations
        $existingAdminIds = DB::table('users')->where('account_type', 'admin')->pluck('id');
        if ($existingAdminIds->isNotEmpty()) {
            DB::table('users')->whereIn('id', $existingAdminIds)->update([
                'account_type' => 'super_admin',
                'updated_at' => $now,
            ]);

            foreach ($existingAdminIds as $userId) {
                if ($adminRoleId) {
                    DB::table('role_user')
                        ->where('user_id', $userId)
                        ->where('role_id', $adminRoleId)
                        ->delete();
                }

                DB::table('role_user')->insertOrIgnore([
                    'user_id' => $userId,
                    'role_id' => $superAdminRoleId,
                ]);
            }
        }

        // 4. Add granular hierarchy permissions
        $newPermissions = [
            ['name' => 'system:manage_admins', 'group_name' => 'system', 'description' => 'Create, edit, deactivate, and manage Command Center Admin accounts'],
            ['name' => 'system:manage_permissions', 'group_name' => 'system', 'description' => 'Manage highest-level system and admin role permissions'],
            ['name' => 'incident:super_override', 'group_name' => 'incidents', 'description' => 'Super Admin override of any incident review, assessment, or assignment'],
            ['name' => 'sos:view_all', 'group_name' => 'sos', 'description' => 'View all Citizen and Personnel SOS alerts'],
            ['name' => 'sos:manage', 'group_name' => 'sos', 'description' => 'Acknowledge, assign responders, escalate, and resolve SOS alerts'],
            ['name' => 'hotlines:manage', 'group_name' => 'system', 'description' => 'Configure DIAL DAPH and LDRRMO emergency hotline settings'],
        ];

        foreach ($newPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'group_name' => $perm['group_name'],
                    'description' => $perm['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Grant all permissions to super_admin role
        $allPermissions = DB::table('permissions')->get();
        $superAdminExclusive = ['system:manage_admins', 'system:manage_permissions', 'system:backups', 'incident:super_override'];

        foreach ($allPermissions as $perm) {
            DB::table('permission_role')->insertOrIgnore([
                'role_id' => $superAdminRoleId,
                'permission_id' => $perm->id,
            ]);

            if (! in_array($perm->name, $superAdminExclusive, true)) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $adminRoleId,
                    'permission_id' => $perm->id,
                ]);
            }
        }

        // 5. Secure Admin invitation/setup table for Super Admin onboarding of Command Center Admins
        Schema::create('admin_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Expand Offices with full municipal organizational metadata & authorized barangays
        DB::statement("ALTER TABLE offices MODIFY COLUMN department_type ENUM('police', 'fire', 'medical', 'disaster', 'traffic', 'health', 'engineering', 'social_welfare', 'other') NOT NULL DEFAULT 'disaster'");

        Schema::table('offices', function (Blueprint $table) {
            $table->text('response_responsibilities_summary')->nullable()->after('description');
            $table->string('head_name', 128)->nullable()->after('contact_email');
            $table->string('head_title', 128)->nullable()->after('head_name');
        });

        Schema::create('office_authorized_barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['office_id', 'barangay_id']);
        });

        // 7. Add explicit account-management grant permission to Office Users
        Schema::table('office_users', function (Blueprint $table) {
            $table->boolean('can_manage_accounts')->default(false)->after('can_dispatch_units');
        });
    }

    public function down(): void
    {
        Schema::table('office_users', function (Blueprint $table) {
            $table->dropColumn('can_manage_accounts');
        });

        Schema::dropIfExists('office_authorized_barangays');

        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['response_responsibilities_summary', 'head_name', 'head_title']);
        });

        DB::statement("ALTER TABLE offices MODIFY COLUMN department_type ENUM('police', 'fire', 'medical', 'disaster', 'traffic') NOT NULL DEFAULT 'disaster'");

        Schema::dropIfExists('admin_invitations');

        // Restore super_admin back to admin cleanly if rolled back
        DB::table('users')->where('account_type', 'super_admin')->update(['account_type' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY COLUMN account_type ENUM('citizen', 'personnel', 'office', 'admin') NOT NULL DEFAULT 'citizen'");
    }
};
