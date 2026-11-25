<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => Str::slug(fake()->userName()).'.'.uniqid().'@example.com',
            'phone_number' => '09'.rand(10, 99).rand(1000000, 9999999),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'account_type' => 'citizen',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function citizen(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'citizen',
        ]);
    }

    public function personnel(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'personnel',
        ]);
    }

    public function office(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'office',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'admin',
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'super_admin',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);
    }
}
