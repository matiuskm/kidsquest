<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use App\Services\PointsLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UndoApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->parent = User::factory()->parent()->create();
        $this->child = User::factory()->child()->create();
    }

    private function balance(): int
    {
        return app(PointsLedger::class)->balance($this->child);
    }

    private function approvedSubmission(): Submission
    {
        $s = Submission::factory()->withPhoto()->create(['task_id' => Task::factory()->create(['points' => 25]), 'child_id' => $this->child->id]);
        $this->actingAs($this->parent)->post(route('parent.submissions.approve', $s));

        return $s->refresh();
    }

    public function test_undo_submission_takes_points_back_and_reopens_review(): void
    {
        $s = $this->approvedSubmission();
        $this->assertSame(25, $this->balance());

        $this->post(route('parent.submissions.undo', $s))->assertSessionHas('status');

        $this->assertSame(ReviewStatus::Pending, $s->refresh()->status);
        $this->assertNull($s->reviewed_at);
        $this->assertSame(0, $this->balance());
    }

    public function test_undo_twice_takes_points_back_once(): void
    {
        $s = $this->approvedSubmission();
        app(PointsLedger::class)->adjust($this->child, 10, 'bonus');

        $this->post(route('parent.submissions.undo', $s));
        $this->post(route('parent.submissions.undo', $s))->assertSessionHasErrors('review');

        $this->assertSame(10, $this->balance());
    }

    public function test_undo_is_blocked_when_points_were_already_spent(): void
    {
        $s = $this->approvedSubmission();
        app(PointsLedger::class)->adjust($this->child, -20, 'dipakai');

        $this->post(route('parent.submissions.undo', $s))->assertSessionHasErrors('review');

        $this->assertSame(ReviewStatus::Approved, $s->refresh()->status);
        $this->assertSame(5, $this->balance());
    }

    public function test_undo_exam_clears_result_and_points(): void
    {
        $e = ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => 95]);
        $this->actingAs($this->parent)->post(route('parent.exams.approve', $e));
        $this->assertSame(5, $this->balance());

        $this->post(route('parent.exams.undo', $e))->assertSessionHas('status');

        $e->refresh();
        $this->assertSame(ReviewStatus::Pending, $e->status);
        $this->assertNull($e->final_score);
        $this->assertNull($e->points_awarded);
        $this->assertSame(0, $this->balance());
    }

    public function test_undo_exam_without_points_needs_no_ledger_entry(): void
    {
        $e = ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => 60]);
        $this->actingAs($this->parent)->post(route('parent.exams.approve', $e));

        $this->post(route('parent.exams.undo', $e))->assertSessionHas('status');

        $this->assertSame(ReviewStatus::Pending, $e->refresh()->status);
    }

    public function test_pending_item_cannot_be_undone_and_child_cannot_undo(): void
    {
        $s = Submission::factory()->withPhoto()->create(['task_id' => Task::factory()->create(), 'child_id' => $this->child->id]);

        $this->actingAs($this->parent)->post(route('parent.submissions.undo', $s))->assertSessionHasErrors('review');
        $this->actingAs($this->child)->post(route('parent.submissions.undo', $s))->assertForbidden();
    }

    public function test_history_shows_undo_button_for_approved_items(): void
    {
        $s = $this->approvedSubmission();

        $this->get(route('parent.history'))->assertSee(route('parent.submissions.undo', $s));
    }
}
