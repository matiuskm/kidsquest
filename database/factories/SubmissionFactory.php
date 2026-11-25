<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/** @extends Factory<Submission> */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'child_id' => User::factory()->child(),
            'period_date' => '2026-11-25',
            'photo_path' => 'photos/test.webp',
            'status' => ReviewStatus::Pending,
        ];
    }

    /** Buat file foto nyata di disk fake agar approve bisa lolos. */
    public function withPhoto(): static
    {
        return $this->afterMaking(fn (Submission $s) => Storage::disk('local')->put($s->photo_path, 'webp'));
    }
}
