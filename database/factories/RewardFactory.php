<?php

namespace Database\Factories;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reward> */
class RewardFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'icon' => '🎁', 'cost' => 60, 'active' => true];
    }
}
