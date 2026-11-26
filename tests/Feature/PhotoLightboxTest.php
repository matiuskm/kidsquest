<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoLightboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_photos_open_in_a_lightbox_instead_of_a_new_tab(): void
    {
        $this->actingAs(User::factory()->parent()->create());
        $pending = ExamResult::factory()->withPhoto()->create();
        $done = ExamResult::factory()->withPhoto()->create(['status' => ReviewStatus::Approved, 'final_score' => 90, 'reviewed_at' => now()]);

        foreach (['parent.dashboard' => $pending, 'parent.history' => $done] as $route => $exam) {
            $this->get(route($route))
                ->assertSee('href="'.route('photos.exam', $exam).'" data-lightbox', false)
                ->assertSee('<dialog id="lightbox"', false)
                ->assertDontSee('target="_blank"', false);
        }
    }
}
