<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\CitizenAddress;
use App\Models\CitizenProfile;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Models\PersonnelAvailability;
use App\Models\PersonnelProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Models\UserPresence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \LogicException('Development accounts cannot be seeded in production.');
        }

        if (! $this->command) {
            throw new \LogicException('Run this development seeder through Artisan to enter its password securely.');
        }

        $password = $this->command->secret('Password for local development accounts');
        if (! is_string($password) || $password === '') {
            throw new \LogicException('A development account password is required.');
        }

        $defaultPassword = Hash::make($password);
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $officeRole = Role::where('name', 'office')->first();
        $personnelRole = Role::where('name', 'personnel')->first();
        $citizenRole = Role::where('name', 'citizen')->first();
        $defaultBarangay = Barangay::first();
        $primaryOffice = Office::first();

        // 1. LDRRMO SUPER ADMIN DEVELOPMENT ACCOUNT (Highest System Authority)
        // New DIAL DAPH Command Center Admins are created strictly through Super Admin UI/invitation flow.
        $superAdminUser = User::firstOrCreate(
            ['phone_number' => '09170000001'],
            [
                'name' => 'ALERTO EOC Director (Dev)',
                'email' => 'admin@alerto.local',
                'password' => $defaultPassword,
                'account_type' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        if ($superAdminUser->account_type !== 'super_admin') {
            $superAdminUser->update(['account_type' => 'super_admin']);
        }
        if ($superAdminRole) {
            $superAdminUser->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
        $this->ensurePresenceAndSettings($superAdminUser);

        // 2. OFFICE ADMIN DEVELOPMENT ACCOUNT
        $officeAdminUser = User::firstOrCreate(
            ['phone_number' => '09170000002'],
            [
                'name' => 'Commander Juan Desk (Dev)',
                'email' => 'office.admin@alerto.local',
                'password' => $defaultPassword,
                'account_type' => 'office',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        if ($officeRole) {
            $officeAdminUser->roles()->syncWithoutDetaching([$officeRole->id]);
        }
        if ($primaryOffice) {
            OfficeUser::firstOrCreate(
                ['office_id' => $primaryOffice->id, 'user_id' => $officeAdminUser->id],
                [
                    'office_level' => 'office_admin',
                    'can_review_incidents' => true,
                    'can_dispatch_units' => true,
                ]
            );
        }
        $this->ensurePresenceAndSettings($officeAdminUser);

        // 3. OFFICE STAFF DEVELOPMENT ACCOUNT
        $officeStaffUser = User::firstOrCreate(
            ['phone_number' => '09170000003'],
            [
                'name' => 'Officer Maria Clerk (Dev)',
                'email' => 'office.staff@alerto.local',
                'password' => $defaultPassword,
                'account_type' => 'office',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        if ($officeRole) {
            $officeStaffUser->roles()->syncWithoutDetaching([$officeRole->id]);
        }
        if ($primaryOffice) {
            OfficeUser::firstOrCreate(
                ['office_id' => $primaryOffice->id, 'user_id' => $officeStaffUser->id],
                [
                    'office_level' => 'office_staff',
                    'can_review_incidents' => false,
                    'can_dispatch_units' => false,
                ]
            );
        }
        $this->ensurePresenceAndSettings($officeStaffUser);

        // 4. PERSONNEL DEVELOPMENT ACCOUNT
        $personnelUser = User::firstOrCreate(
            ['phone_number' => '09170000004'],
            [
                'name' => 'Responder Alex Cruz (Dev)',
                'email' => 'personnel.medic@alerto.local',
                'password' => $defaultPassword,
                'account_type' => 'personnel',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        if ($personnelRole) {
            $personnelUser->roles()->syncWithoutDetaching([$personnelRole->id]);
        }
        if ($primaryOffice) {
            $personnelProfile = PersonnelProfile::firstOrCreate(
                ['user_id' => $personnelUser->id],
                [
                    'office_id' => $primaryOffice->id,
                    'badge_number' => 'DEV-PNP-001',
                    'rank_designation' => 'Police Patrolman',
                    'specialization' => 'First Aid & Tactical Response',
                    'is_team_leader' => false,
                ]
            );

            PersonnelAvailability::firstOrCreate(
                ['personnel_id' => $personnelProfile->id],
                [
                    'operational_status' => 'available',
                    'status_reason' => 'On Duty (Dev Session)',
                    'status_updated_at' => now(),
                ]
            );
        }
        $this->ensurePresenceAndSettings($personnelUser);

        // 5. CITIZEN DEVELOPMENT ACCOUNT
        $citizenUser = User::firstOrCreate(
            ['phone_number' => '09170000005'],
            [
                'name' => 'Citizen Pedro Santos (Dev)',
                'email' => 'citizen.demo@alerto.local',
                'password' => $defaultPassword,
                'account_type' => 'citizen',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        if ($citizenRole) {
            $citizenUser->roles()->syncWithoutDetaching([$citizenRole->id]);
        }
        CitizenProfile::firstOrCreate(
            ['user_id' => $citizenUser->id],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Santos',
                'verified_citizen' => true,
                'blood_type' => 'O+',
                'emergency_contact_name' => 'Elena Santos',
                'emergency_contact_phone' => '09179998877',
            ]
        );
        if ($defaultBarangay) {
            CitizenAddress::firstOrCreate(
                ['user_id' => $citizenUser->id, 'barangay_id' => $defaultBarangay->id],
                [
                    'street_address' => '123 Pioneer Avenue, Purok Malakas',
                    'latitude' => 6.1150,
                    'longitude' => 125.1740,
                    'is_primary' => true,
                ]
            );
        }
        $this->ensurePresenceAndSettings($citizenUser);
    }

    private function ensurePresenceAndSettings(User $user): void
    {
        UserPresence::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]
        );

        UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'enable_push' => true,
                'enable_sms' => true,
                'enable_email' => false,
                'enable_sound' => true,
            ]
        );
    }
}
