<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => Role::Parent,
            'avatar' => fake()->randomElement(config('kidsquest.avatars')),
        ];
    }

    public function parent(): static
    {
        return $this->state(['role' => Role::Parent]);
    }

    public function child(string $pin = '1234', int $month = 6, int $day = 15): static
    {
        return $this->state([
            'role' => Role::Child,
            'email' => null,
            'password' => null,
            'pin_hash' => $pin,
            'birthday_month' => $month,
            'birthday_day' => $day,
        ]);
    }
}
