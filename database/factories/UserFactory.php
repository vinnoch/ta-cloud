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
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'mahasiswa',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function kaprodi(): static
    {
        return $this->state(fn () => ['role' => 'kaprodi']);
    }

    public function dosen(): static
    {
        return $this->state(fn () => ['role' => 'dosen', 'nim' => null, 'nidn_nip' => fake()->numerify('##########')]);
    }

    public function mahasiswa(): static
    {
        return $this->state(fn () => ['role' => 'mahasiswa', 'nim' => fake()->numerify('2021########')]);
    }
}
