<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IncidentCategory;
use App\Models\IncidentRoutingRule;
use App\Models\Office;
use Illuminate\Database\Seeder;

class IncidentRoutingRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \LogicException('Operational incident routing rules cannot be seeded in production.');
        }

        $pnp = Office::where('department_type', 'police')->first() ?? Office::find(1);
        $bfp = Office::where('department_type', 'fire')->first() ?? Office::find(2);
        $cdrrmo = Office::where('department_type', 'disaster')->first() ?? Office::find(3);
        $ems = Office::where('department_type', 'medical')->first() ?? Office::find(4);

        if (! $pnp || ! $bfp || ! $cdrrmo || ! $ems) {
            return;
        }

        $rules = [
            // Fire categories -> BFP
            ['slug' => 'fire-incident', 'target' => $bfp->id, 'fallback' => $cdrrmo->id, 'priority' => 'critical'],
            ['slug' => 'fire-emergency', 'target' => $bfp->id, 'fallback' => $cdrrmo->id, 'priority' => 'critical'],
            // Medical categories -> EMS
            ['slug' => 'medical-emergency', 'target' => $ems->id, 'fallback' => $cdrrmo->id, 'priority' => 'critical'],
            // Security & Police categories -> PNP
            ['slug' => 'crime-security', 'target' => $pnp->id, 'fallback' => $cdrrmo->id, 'priority' => 'high'],
            ['slug' => 'road-accident', 'target' => $pnp->id, 'fallback' => $ems->id, 'priority' => 'high'],
            // Disaster & Rescue -> CDRRMO
            ['slug' => 'disaster-rescue', 'target' => $cdrrmo->id, 'fallback' => $bfp->id, 'priority' => 'critical'],
            ['slug' => 'flooding', 'target' => $cdrrmo->id, 'fallback' => $bfp->id, 'priority' => 'critical'],
            ['slug' => 'landslide', 'target' => $cdrrmo->id, 'fallback' => $bfp->id, 'priority' => 'critical'],
            ['slug' => 'rescue-needed', 'target' => $cdrrmo->id, 'fallback' => $bfp->id, 'priority' => 'critical'],
            ['slug' => 'missing-person', 'target' => $pnp->id, 'fallback' => $cdrrmo->id, 'priority' => 'high'],
            ['slug' => 'fallen-tree', 'target' => $cdrrmo->id, 'fallback' => $bfp->id, 'priority' => 'medium'],
            ['slug' => 'traffic-hazard', 'target' => $pnp->id, 'fallback' => $cdrrmo->id, 'priority' => 'medium'],
            ['slug' => 'other-incident', 'target' => $cdrrmo->id, 'fallback' => null, 'priority' => 'medium'],
        ];

        foreach ($rules as $item) {
            $category = IncidentCategory::where('slug', $item['slug'])->first();
            if ($category) {
                IncidentRoutingRule::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'barangay_id' => null, // Applies to all barangays unless overridden
                    ],
                    [
                        'priority' => $item['priority'],
                        'target_office_id' => $item['target'],
                        'fallback_office_id' => $item['fallback'],
                        'auto_dispatch' => false,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
