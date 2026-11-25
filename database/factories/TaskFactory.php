<?php

namespace Database\Factories;

use App\Enums\Recurrence;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'points' => 10,
            'recurrence' => Recurrence::Daily,
            'start_date' => '2026-01-01',
            'active' => true,
        ];
    }

    public function weekly(array $weekdays): static
    {
        return $this->state(['recurrence' => Recurrence::Weekly, 'weekdays' => $weekdays]);
    }

    public function monthly(int $day): static
    {
        return $this->state(['recurrence' => Recurrence::Monthly, 'month_day' => $day]);
    }

    public function once(): static
    {
        return $this->state(['recurrence' => Recurrence::Once]);
    }
}
