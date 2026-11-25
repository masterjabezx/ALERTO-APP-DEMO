<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarangayFactory extends Factory
{
    protected $model = Barangay::class;

    public function definition(): array
    {
        return [
            'municipality_id' => Municipality::factory(),
            'name' => 'Barangay '.fake()->unique()->streetName(),
            'contact_number' => fake()->phoneNumber(),
            'center_latitude' => fake()->latitude(6.0, 6.3),
            'center_longitude' => fake()->longitude(125.0, 125.3),
        ];
    }
}
