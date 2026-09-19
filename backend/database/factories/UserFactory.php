<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Test/development factory. Admin accounts are created explicitly (php
 * artisan tinker) - never by random seeding.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'name' => fake()->name(),
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'phone' => null,
            'phone_index' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_VENDOR,
            'district_id' => null,
            'is_active' => true,
        ];
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_VENDOR,
        ]);
    }

    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_DRIVER,
        ]);
    }

    public function skilledWorker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SKILLED_WORKER,
        ]);
    }

    public function volunteer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_VOLUNTEER,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
