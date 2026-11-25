<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/** @extends Factory<ExamResult> */
class ExamResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id' => User::factory()->child(),
            'subject' => 'Matematika',
            'claimed_score' => 85,
            'photo_path' => 'photos/exam-test.webp',
            'status' => ReviewStatus::Pending,
        ];
    }

    /** Buat file foto nyata di disk fake agar approve bisa lolos. */
    public function withPhoto(): static
    {
        return $this->afterMaking(fn (ExamResult $e) => Storage::disk('local')->put($e->photo_path, 'webp'));
    }
}
