<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed baseline roles and reference data without accounts or operational contacts.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            GeographicSeeder::class,
            IncidentCategorySeeder::class,
        ]);
    }
}
