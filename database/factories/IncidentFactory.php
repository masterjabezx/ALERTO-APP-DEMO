<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'reference_number' => 'ALT-'.date('Ymd').'-'.fake()->unique()->numberBetween(10000, 99999),
            'reporter_id' => User::factory()->citizen(),
            'source_channel' => 'citizen_portal',
            'category_id' => IncidentCategory::factory(),
            'priority' => 'medium',
            'status' => 'submitted',
            'description' => fake()->paragraph(),
            'caller_name' => fake()->name(),
            'caller_phone' => fake()->numerify('09#########'),
            'barangay_id' => Barangay::factory(),
            'submitted_at' => now(),
            'version_lock' => 1,
        ];
    }
}
