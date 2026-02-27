<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'           => fake()->name(),
            'email'          => fake()->unique()->safeEmail(),
            'phone'          => fake()->unique()->numerify('968########'),
            'password'       => static::$password ??= Hash::make('password'),
            'role'           => 'customer',
            'is_active'      => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin'])
            ->afterCreating(fn($u) => $u->assignRole('admin'));
    }

    public function technician(): static
    {
        return $this->state(['role' => 'technician'])
            ->afterCreating(fn($u) => $u->assignRole('technician'));
    }

    public function customer(): static
    {
        return $this->state(['role' => 'customer'])
            ->afterCreating(fn($u) => $u->assignRole('customer'));
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withOdoo(): static
    {
        return $this->state(['odoo_id' => fake()->numberBetween(1000, 9999)]);
    }
}
