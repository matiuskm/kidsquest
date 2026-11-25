<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Enums\ReviewStatus;
use App\Exceptions\AlreadyReviewed;
use App\Models\PointTransaction;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use App\Services\PointsLedger;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->parent = User::factory()->parent()->create();
        $this->child = User::factory()->child()->create(['name' => 'Budi']);
        $task = Task::factory()->create(['title' => 'Cuci piring', 'points' => 25]);
        $this->submission = Submission::factory()->withPhoto()->create(['task_id' => $task->id, 'child_id' => $this->child->id]);
    }

    private function balance(): int
    {
        return app(PointsLedger::class)->balance($this->child);
    }

    public function test_approve_adds_task_points_and_marks_reviewed(): void
    {
        $this->actingAs($this->parent)->post(route('parent.submissions.approve', $this->submission))->assertRedirect();

        $this->submission->refresh();
        $this->assertSame(ReviewStatus::Approved, $this->submission->status);
        $this->assertNotNull($this->submission->reviewed_at);
        $this->assertSame(25, $this->balance());
    }

    public function test_approve_twice_writes_a_single_ledger_entry(): void
    {
        $this->actingAs($this->parent)->post(route('parent.submissions.approve', $this->submission));
        $this->post(route('parent.submissions.approve', $this->submission))->assertSessionHasErrors('review');

        $this->assertSame(25, $this->balance());
        $this->assertSame(1, PointTransaction::where('type', LedgerType::Earn)->count());
    }

    public function test_service_rejects_second_approval(): void
    {
        $service = app(ReviewService::class);
        $service->approveSubmission($this->submission);

        $this->expectException(AlreadyReviewed::class);
        $service->approveSubmission($this->submission->fresh());
    }

    public function test_reject_stores_note_and_gives_no_points(): void
    {
        $this->actingAs($this->parent)->post(route('parent.submissions.reject', $this->submission), ['note' => 'Fotonya buram'])
            ->assertRedirect();

        $this->submission->refresh();
        $this->assertSame(ReviewStatus::Rejected, $this->submission->status);
        $this->assertSame('Fotonya buram', $this->submission->reject_note);
        $this->assertSame(0, $this->balance());
    }

    public function test_rejected_submission_cannot_be_approved_later(): void
    {
        $this->actingAs($this->parent)->post(route('parent.submissions.reject', $this->submission));
        $this->post(route('parent.submissions.approve', $this->submission))->assertSessionHasErrors('review');

        $this->assertSame(0, $this->balance());
    }

    public function test_child_cannot_review(): void
    {
        $this->actingAs($this->child)->post(route('parent.submissions.approve', $this->submission))->assertForbidden();
        $this->post(route('parent.submissions.reject', $this->submission))->assertForbidden();

        $this->assertSame(ReviewStatus::Pending, $this->submission->fresh()->status);
        $this->assertSame(0, $this->balance());
    }

    public function test_approve_without_photo_file_is_refused(): void
    {
        Storage::disk('local')->delete($this->submission->photo_path);

        $this->actingAs($this->parent)->post(route('parent.submissions.approve', $this->submission))
            ->assertSessionHasErrors('review');

        $this->assertSame(ReviewStatus::Pending, $this->submission->fresh()->status);
        $this->assertSame(0, $this->balance());
    }

    public function test_submission_of_a_soft_deleted_task_can_still_be_approved(): void
    {
        $this->submission->task->delete();

        $this->actingAs($this->parent)->get(route('parent.dashboard'))->assertOk()->assertSee('Cuci piring');
        $this->post(route('parent.submissions.approve', $this->submission))->assertSessionHasNoErrors();

        $this->assertSame(25, $this->balance());
    }

    public function test_dashboard_lists_pending_queue_and_children_balances(): void
    {
        app(PointsLedger::class)->earn($this->child, 40, $this->child);

        $this->actingAs($this->parent)->get(route('parent.dashboard'))->assertOk()
            ->assertSee('Perlu diperiksa')->assertSee('Budi')->assertSee('Cuci piring')->assertSee('40');
    }

    public function test_history_shows_decisions_and_point_movements(): void
    {
        $this->actingAs($this->parent)->post(route('parent.submissions.approve', $this->submission));
        app(PointsLedger::class)->adjust($this->child, -5, 'koreksi manual');

        $this->get(route('parent.history'))->assertOk()
            ->assertSee('Cuci piring')->assertSee('koreksi manual')->assertSee('+25');
        $this->get(route('parent.history', ['anak' => User::factory()->child()->create()->id]))
            ->assertOk()->assertDontSee('Cuci piring');
    }
}
