<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'reception',
        ];
    }

    /**
     * États personnalisés : permettent de générer un utilisateur
     * avec un rôle précis, ex: User::factory()->technicien()->create()
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'manager']);
    }

    public function technicien(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'technicien']);
    }

    public function housekeeping(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'housekeeping']);
    }

    public function reception(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'reception']);
    }
}