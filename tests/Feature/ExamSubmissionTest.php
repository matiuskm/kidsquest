<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExamSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->child = User::factory()->child()->create();
    }

    private function send(array $data = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->child)->post(route('child.exams.store'), $data + [
            'subject' => 'Matematika',
            'score' => 95,
            'photo' => UploadedFile::fake()->image('u.jpg', 1600, 1200),
        ]);
    }

    public function test_child_submits_score_with_photo(): void
    {
        $this->send()->assertRedirect(route('child.exams'));

        $exam = ExamResult::firstOrFail();
        $this->assertSame(ReviewStatus::Pending, $exam->status);
        $this->assertSame(95, $exam->claimed_score);
        $this->assertSame('Matematika', $exam->subject);
        $this->assertTrue($exam->child->is($this->child));
        Storage::disk('local')->assertExists($exam->photo_path);
    }

    public function test_invalid_scores_are_rejected(): void
    {
        foreach ([101, -1, 85.5, 'abc', ''] as $score) {
            $this->send(['score' => $score])->assertSessionHasErrors('score');
        }

        $this->assertSame(0, ExamResult::count());
    }

    public function test_child_submits_a_letter_grade(): void
    {
        $this->send(['subject' => 'Bahasa Inggris', 'score' => null, 'letter' => 'b'])->assertRedirect(route('child.exams'));

        $exam = ExamResult::firstOrFail();
        $this->assertSame('B', $exam->claimed_letter);
        $this->assertNull($exam->claimed_score);
    }

    public function test_letter_must_be_valid_and_exclusive_with_score(): void
    {
        $this->send(['score' => null, 'letter' => 'Z'])->assertSessionHasErrors('letter');
        $this->send(['score' => 90, 'letter' => 'A'])->assertSessionHasErrors();
        $this->send(['score' => null])->assertSessionHasErrors('score');
        $this->assertSame(0, ExamResult::count());
    }

    public function test_photo_and_subject_are_required(): void
    {
        $this->actingAs($this->child)->post(route('child.exams.store'), ['subject' => 'IPA', 'score' => 80])
            ->assertSessionHasErrors('photo');
        $this->actingAs($this->child)->post(route('child.exams.store'), ['score' => 80, 'photo' => UploadedFile::fake()->image('u.jpg')])
            ->assertSessionHasErrors('subject');
        $this->assertSame(0, ExamResult::count());
    }

    public function test_double_tap_creates_a_single_row(): void
    {
        $this->send();
        $this->send()->assertSessionHasErrors('photo');

        $this->assertSame(1, ExamResult::count());
    }

    public function test_pending_exam_blocks_resubmission_until_decided(): void
    {
        $this->send();
        $this->travel(1)->minute();
        $this->send()->assertSessionHasErrors('photo');
        $this->send(['subject' => 'IPA'])->assertSessionDoesntHaveErrors();
        $this->assertSame(2, ExamResult::count());

        ExamResult::where('subject', 'Matematika')->update(['status' => ReviewStatus::Rejected]);
        $this->send()->assertSessionDoesntHaveErrors();
        $this->assertSame(3, ExamResult::count());
    }

    public function test_exam_photo_is_visible_to_owner_and_parent_only(): void
    {
        $this->send();
        $exam = ExamResult::firstOrFail();
        $url = route('photos.exam', $exam);

        $this->actingAs(User::factory()->child()->create())->get($url)->assertForbidden();
        $this->actingAs($this->child)->get($url)->assertOk();
        $this->actingAs(User::factory()->parent()->create())->get($url)->assertOk();
        auth()->logout();
        $this->get($url)->assertRedirect();
    }

    public function test_exam_page_lists_only_own_results(): void
    {
        $mine = ExamResult::factory()->create(['child_id' => $this->child->id, 'subject' => 'Bahasa Inggris']);
        ExamResult::factory()->create(['subject' => 'Rahasia Saudara']);

        $this->actingAs($this->child)->get(route('child.exams'))
            ->assertOk()->assertSee($mine->subject)->assertDontSee('Rahasia Saudara');
    }
}
