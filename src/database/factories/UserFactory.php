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
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function user(): static
    {
        return $this->state(fn() => [
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn() => [
            'email_verified_at' => null,
        ]);
    }

    public function named(string $name): static
    {
        return $this->state(fn() => [
            'name' => $name,
        ]);
    }

    public function withEmail(string $email): static
    {
        return $this->state(fn() => [
            'email' => $email,
        ]);
    }
}
