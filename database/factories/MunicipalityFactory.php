<?php

namespace Database\Factories;

use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

class MunicipalityFactory extends Factory
{
    protected $model = Municipality::class;

    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'province' => fake()->state(),
            'region' => 'Region XII',
            'postal_code' => fake()->postcode(),
            'center_latitude' => fake()->latitude(5.0, 7.0),
            'center_longitude' => fake()->longitude(124.0, 126.0),
        ];
    }
}
