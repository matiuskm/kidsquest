<?php

namespace Tests\Feature;

use App\Enums\MissionStatus;
use App\Enums\ReviewStatus;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use App\Services\MissionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissionTest extends TestCase
{
    use RefreshDatabase;

    private User $child;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo('2026-11-25 10:00:00'); // Rabu, Asia/Jakarta

        $this->child = User::factory()->child()->create();
        $this->task = Task::factory()->create(['title' => 'Sikat gigi', 'points' => 5]);
        $this->task->children()->attach($this->child);
    }

    private function submit(?User $as = null, ?Task $task = null, ?UploadedFile $photo = null)
    {
        return $this->actingAs($as ?? $this->child)->post(
            route('child.submit', $task ?? $this->task),
            ['photo' => $photo ?? UploadedFile::fake()->image('p.jpg', 1600, 1200)],
        );
    }

    private function statusOf(User $child, Task $task): ?MissionStatus
    {
        return app(MissionService::class)->todayFor($child, CarbonImmutable::now())
            ->first(fn ($m) => $m->task->is($task))?->status;
    }

    public function test_bottom_menu_is_short_and_marks_the_active_tab(): void
    {
        $html = $this->actingAs($this->child)->get(route('child.missions'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Ganti anak', $html);
        $this->assertStringContainsString('>Keluar<', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_child_sees_only_own_tasks(): void
    {
        $other = Task::factory()->create(['title' => 'Milik adik']);
        $other->children()->attach(User::factory()->child()->create());

        $this->actingAs($this->child)->get(route('child.missions'))
            ->assertOk()->assertSee('Sikat gigi')->assertDontSee('Milik adik');
    }

    public function test_submit_creates_pending_submission_with_webp_photo(): void
    {
        $this->submit()->assertRedirect(route('child.missions'));

        $s = Submission::sole();
        $this->assertSame(ReviewStatus::Pending, $s->status);
        $this->assertSame('2026-11-25', $s->period_date->toDateString());
        $this->assertStringEndsWith('.webp', $s->photo_path);
        Storage::disk('local')->assertExists($s->photo_path);
    }

    public function test_status_flow_todo_pending_done(): void
    {
        $this->assertSame(MissionStatus::Todo, $this->statusOf($this->child, $this->task));

        $this->submit();
        $this->assertSame(MissionStatus::Pending, $this->statusOf($this->child, $this->task));

        Submission::sole()->update(['status' => ReviewStatus::Approved]);
        $this->assertSame(MissionStatus::Done, $this->statusOf($this->child, $this->task));
    }

    public function test_task_not_due_today_is_rejected(): void
    {
        $task = Task::factory()->weekly([1])->create(); // Senin, bukan Rabu
        $task->children()->attach($this->child);

        $this->submit(task: $task)->assertSessionHasErrors('photo');
        $this->assertSame(0, Submission::count());
    }

    public function test_task_not_assigned_is_rejected(): void
    {
        $task = Task::factory()->create();

        $this->submit(task: $task)->assertSessionHasErrors('photo');
        $this->assertSame(0, Submission::count());
    }

    public function test_second_submit_is_blocked(): void
    {
        $this->submit();
        $this->submit()->assertSessionHasErrors('photo');

        $this->assertSame(1, Submission::count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_approved_mission_cannot_be_redone_by_child(): void
    {
        $this->submit();
        $submission = Submission::firstOrFail();
        $submission->update(['status' => ReviewStatus::Approved]);

        $this->submit()->assertSessionHasErrors('photo');

        $this->assertSame(1, Submission::count());
        $this->assertSame(ReviewStatus::Approved, $submission->fresh()->status);
    }

    public function test_resubmit_after_rejection_is_allowed(): void
    {
        $this->submit();
        Submission::sole()->update(['status' => ReviewStatus::Rejected]);
        $this->assertSame(MissionStatus::Rejected, $this->statusOf($this->child, $this->task));

        $this->submit()->assertSessionHasNoErrors();

        $this->assertSame(2, Submission::count());
        $this->assertSame(MissionStatus::Pending, $this->statusOf($this->child, $this->task));
    }

    public function test_non_image_and_oversize_uploads_are_rejected(): void
    {
        $this->submit(photo: UploadedFile::fake()->createWithContent('a.jpg', 'teks biasa'))->assertSessionHasErrors('photo');
        $this->submit(photo: UploadedFile::fake()->image('big.jpg')->size(11000))->assertSessionHasErrors('photo');

        $this->assertSame(0, Submission::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_photo_access_is_limited_to_parent_and_owner(): void
    {
        $this->submit();
        $s = Submission::sole();

        $this->actingAs($this->child)->get(route('photos.show', $s))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->actingAs(User::factory()->parent()->create())->get(route('photos.show', $s))->assertOk();
        $this->actingAs(User::factory()->child()->create())->get(route('photos.show', $s))->assertForbidden();
    }

    public function test_guest_cannot_see_photo(): void
    {
        $this->submit();

        $this->app['auth']->forgetGuards();
        $this->get(route('photos.show', Submission::sole()))->assertRedirect(route('login'));
    }

    public function test_once_task_hides_after_done_day_but_stays_for_sibling(): void
    {
        $sibling = User::factory()->child()->create();
        $once = Task::factory()->once()->create(['start_date' => '2026-11-20']);
        $once->children()->attach([$this->child->id, $sibling->id]);
        Submission::create([
            'task_id' => $once->id, 'child_id' => $this->child->id, 'period_date' => '2026-11-20',
            'photo_path' => 'x.webp', 'status' => ReviewStatus::Approved, 'reviewed_at' => '2026-11-24 09:00:00',
        ]);

        $this->assertNull($this->statusOf($this->child, $once));
        $this->assertSame(MissionStatus::Todo, $this->statusOf($sibling, $once));

        Submission::where('task_id', $once->id)->update(['reviewed_at' => '2026-11-25 08:00:00']);
        $this->assertSame(MissionStatus::Done, $this->statusOf($this->child, $once));
    }

    public function test_once_task_cannot_be_submitted_twice(): void
    {
        $once = Task::factory()->once()->create(['start_date' => '2026-11-20']);
        $once->children()->attach($this->child);

        $this->submit(task: $once);
        $this->travelTo('2026-11-26 10:00:00');
        $this->submit(task: $once)->assertSessionHasErrors('photo');

        $this->assertSame(1, Submission::where('task_id', $once->id)->count());
    }
}
