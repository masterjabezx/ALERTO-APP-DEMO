<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IncidentCategory;
use Illuminate\Database\Seeder;

class IncidentCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 9 Official Initial Categories required by ALERTO APP Master Spec
        $categories = [
            [
                'name' => 'Fire Incident',
                'slug' => 'fire-incident',
                'description' => 'Structural, residential, forest, or vehicular fire requiring BFP deployment.',
                'default_priority' => 'critical',
                'icon' => 'flame',
                'color_code' => '#DB4545',
            ],
            [
                'name' => 'Medical Emergency',
                'slug' => 'medical-emergency',
                'description' => 'Cardiac event, respiratory arrest, severe trauma, obstetric or acute health emergencies.',
                'default_priority' => 'critical',
                'icon' => 'heart-pulse',
                'color_code' => '#DB4545',
            ],
            [
                'name' => 'Road Accident',
                'slug' => 'road-accident',
                'description' => 'Motorcycle, vehicle, truck, or pedestrian vehicular collision and road hazards.',
                'default_priority' => 'high',
                'icon' => 'car',
                'color_code' => '#F0A82E',
            ],
            [
                'name' => 'Flooding',
                'slug' => 'flooding',
                'description' => 'River overflow, flash flood, rising water levels, and submerged residential areas.',
                'default_priority' => 'critical',
                'icon' => 'cloud-rain',
                'color_code' => '#3187D8',
            ],
            [
                'name' => 'Landslide',
                'slug' => 'landslide',
                'description' => 'Soil erosion, slope failure, rockfall, and blocked roads due to ground movement.',
                'default_priority' => 'critical',
                'icon' => 'mountain',
                'color_code' => '#92400E',
            ],
            [
                'name' => 'Rescue Needed',
                'slug' => 'rescue-needed',
                'description' => 'Trapped individuals, sea or river distress, collapsed structures, or confined spaces.',
                'default_priority' => 'critical',
                'icon' => 'life-buoy',
                'color_code' => '#19865B',
            ],
            [
                'name' => 'Missing Person',
                'slug' => 'missing-person',
                'description' => 'Lost child, elderly with dementia, missing fisherman at sea, or unaccounted resident.',
                'default_priority' => 'high',
                'icon' => 'user-x',
                'color_code' => '#6366F1',
            ],
            [
                'name' => 'Fallen Tree',
                'slug' => 'fallen-tree',
                'description' => 'Obstructed roads, fallen powerlines, damaged houses or infrastructure from fallen timber.',
                'default_priority' => 'medium',
                'icon' => 'trees',
                'color_code' => '#059669',
            ],
            [
                'name' => 'Other Incident',
                'slug' => 'other-incident',
                'description' => 'Public safety concerns, chemical/gas leaks, power outages, and miscellaneous incidents.',
                'default_priority' => 'medium',
                'icon' => 'alert-circle',
                'color_code' => '#64748B',
            ],
        ];

        foreach ($categories as $cat) {
            IncidentCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
