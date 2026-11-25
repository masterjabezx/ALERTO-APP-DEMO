<?php

namespace Database\Factories;

use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Station',
            'code' => fake()->unique()->lexify('OFFICE_????'),
            'department_type' => fake()->randomElement(['police', 'fire', 'medical', 'disaster', 'traffic']),
            'contact_phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
