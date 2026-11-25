<?php

namespace Database\Factories;

use App\Models\IncidentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IncidentCategoryFactory extends Factory
{
    protected $model = IncidentCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Emergency';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'default_priority' => fake()->randomElement(['critical', 'high', 'medium', 'low']),
            'icon' => 'alert-triangle',
            'color_code' => '#EF4444',
            'is_active' => true,
        ];
    }
}
