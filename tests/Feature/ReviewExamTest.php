<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointsLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewExamTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    private ExamResult $exam;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->parent = User::factory()->parent()->create();
        $this->child = User::factory()->child()->create(['name' => 'Budi']);
        $this->exam = $this->makeExam(95);
    }

    private function makeExam(int $score): ExamResult
    {
        return ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => $score, 'subject' => 'IPA']);
    }

    private function balance(): int
    {
        return app(PointsLedger::class)->balance($this->child);
    }

    private function approve(ExamResult $exam, array $data = [])
    {
        return $this->actingAs($this->parent)->post(route('parent.exams.approve', $exam), $data);
    }

    public function test_approve_awards_points_for_claimed_score(): void
    {
        $this->approve($this->exam)->assertRedirect();

        $this->exam->refresh();
        $this->assertSame(ReviewStatus::Approved, $this->exam->status);
        $this->assertSame(95, $this->exam->final_score);
        $this->assertSame(5, $this->exam->points_awarded);
        $this->assertNotNull($this->exam->reviewed_at);
        $this->assertSame(5, $this->balance());
    }

    public function test_parent_correction_changes_points(): void
    {
        $this->approve($this->exam, ['score' => 75]);

        $this->assertSame(75, $this->exam->refresh()->final_score);
        $this->assertSame(3, $this->balance());
    }

    public function test_score_below_seventy_is_approved_without_points(): void
    {
        $exam = $this->makeExam(60);
        $this->approve($exam);

        $this->assertSame(ReviewStatus::Approved, $exam->refresh()->status);
        $this->assertSame(0, $exam->points_awarded);
        $this->assertSame(0, $this->balance());
        $this->assertSame(0, PointTransaction::where('type', LedgerType::Exam)->count());
    }

    public function test_letter_exam_awards_points_by_letter(): void
    {
        $exam = ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => null, 'claimed_letter' => 'B']);
        $this->approve($exam);

        $exam->refresh();
        $this->assertSame('B', $exam->final_letter);
        $this->assertNull($exam->final_score);
        $this->assertSame(4, $exam->points_awarded);
        $this->assertSame(4, $this->balance());
    }

    public function test_letter_correction_and_low_letter(): void
    {
        $exam = ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => null, 'claimed_letter' => 'A']);
        $this->approve($exam, ['letter' => 'C']);
        $this->assertSame(3, $this->balance());

        $low = ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => null, 'claimed_letter' => 'D']);
        $this->approve($low);
        $this->assertSame(0, $low->refresh()->points_awarded);
        $this->assertSame(3, $this->balance());

        $this->approve(ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'claimed_score' => null, 'claimed_letter' => 'A']), ['letter' => 'Z'])
            ->assertSessionHasErrors('letter');
    }

    public function test_approve_twice_writes_one_ledger_entry(): void
    {
        $this->approve($this->exam);
        $this->post(route('parent.exams.approve', $this->exam))->assertSessionHasErrors('review');

        $this->assertSame(5, $this->balance());
        $this->assertSame(1, PointTransaction::where('type', LedgerType::Exam)->count());
    }

    public function test_out_of_range_correction_is_rejected(): void
    {
        $this->approve($this->exam, ['score' => 101])->assertSessionHasErrors('score');

        $this->assertSame(ReviewStatus::Pending, $this->exam->refresh()->status);
        $this->assertSame(0, $this->balance());
    }

    public function test_missing_photo_blocks_approval(): void
    {
        Storage::disk('local')->delete($this->exam->photo_path);
        $this->approve($this->exam)->assertSessionHasErrors('review');

        $this->assertSame(ReviewStatus::Pending, $this->exam->refresh()->status);
        $this->assertSame(0, $this->balance());
    }

    public function test_reject_records_note_and_gives_no_points(): void
    {
        $this->actingAs($this->parent)->post(route('parent.exams.reject', $this->exam), ['note' => 'Foto buram'])->assertRedirect();

        $this->exam->refresh();
        $this->assertSame(ReviewStatus::Rejected, $this->exam->status);
        $this->assertSame('Foto buram', $this->exam->reject_note);
        $this->assertSame(0, $this->balance());
    }

    public function test_child_cannot_review(): void
    {
        $this->actingAs($this->child)->post(route('parent.exams.approve', $this->exam))->assertForbidden();

        $this->assertSame(ReviewStatus::Pending, $this->exam->refresh()->status);
    }

    public function test_dashboard_queue_and_history_show_exams(): void
    {
        $this->actingAs($this->parent)->get(route('parent.dashboard'))
            ->assertOk()->assertSee('IPA')->assertSee(route('photos.exam', $this->exam));

        $this->approve($this->exam);
        $this->get(route('parent.history'))->assertOk()->assertSee('IPA')->assertSee('Nilai ulangan');
    }
}
