<?php

namespace Tests\Feature;

use App\Enums\Recurrence;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parent = User::factory()->parent()->create();
        $this->child = User::factory()->child()->create(['name' => 'Budi']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Rapikan tempat tidur',
            'description' => 'Sebelum sarapan',
            'points' => 10,
            'recurrence' => 'daily',
            'start_date' => '2026-11-25',
            'children' => [$this->child->id],
            'active' => 1,
        ], $overrides);
    }

    public function test_start_date_and_recurrence_are_locked_once_task_has_submissions(): void
    {
        $task = Task::factory()->create(['recurrence' => 'once', 'start_date' => '2026-11-25']);
        $task->children()->attach($this->child);
        \App\Models\Submission::factory()->create(['task_id' => $task->id, 'child_id' => $this->child->id]);
        $this->actingAs($this->parent);

        $this->put(route('parent.tasks.update', $task), $this->payload(['recurrence' => 'once', 'start_date' => '2026-11-26']))
            ->assertSessionHasErrors('start_date');
        $this->put(route('parent.tasks.update', $task), $this->payload(['recurrence' => 'daily', 'start_date' => '2026-11-25']))
            ->assertSessionHasErrors('recurrence');
        $this->put(route('parent.tasks.update', $task), $this->payload(['recurrence' => 'once', 'start_date' => '2026-11-25', 'title' => 'Judul baru']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Judul baru', $task->fresh()->title);
        $this->assertSame('2026-11-25', $task->fresh()->start_date->toDateString());
    }

    public function test_parent_sees_task_list(): void
    {
        Task::factory()->create(['title' => 'Sikat gigi']);

        $this->actingAs($this->parent)->get(route('parent.tasks.index'))->assertOk()->assertSee('Sikat gigi');
    }

    public function test_parent_creates_task_and_assigns_children(): void
    {
        $this->actingAs($this->parent)->post(route('parent.tasks.store'), $this->payload())
            ->assertRedirect(route('parent.tasks.index'));

        $task = Task::firstOrFail();
        $this->assertSame(10, $task->points);
        $this->assertSame(Recurrence::Daily, $task->recurrence);
        $this->assertSame([$this->child->id], $task->children->pluck('id')->all());
    }

    public function test_weekly_task_stores_weekdays_as_ints_and_clears_month_day(): void
    {
        $this->actingAs($this->parent)->post(route('parent.tasks.store'), $this->payload([
            'recurrence' => 'weekly', 'weekdays' => ['1', '3'], 'month_day' => 5,
        ]))->assertSessionHasNoErrors();

        $task = Task::firstOrFail();
        $this->assertSame([1, 3], $task->weekdays);
        $this->assertNull($task->month_day);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $bad = [
            ['points' => 0],
            ['points' => 'abc'],
            ['recurrence' => 'weekly'],
            ['recurrence' => 'weekly', 'weekdays' => [9]],
            ['recurrence' => 'monthly'],
            ['recurrence' => 'monthly', 'month_day' => 32],
            ['end_date' => '2026-11-01'],
            ['children' => []],
            ['children' => [$this->parent->id]],
            ['title' => ''],
        ];

        foreach ($bad as $i => $override) {
            $this->actingAs($this->parent)->post(route('parent.tasks.store'), $this->payload($override))
                ->assertSessionHasErrors(null, null, "case $i");
        }
        $this->assertSame(0, Task::count());
    }

    public function test_parent_updates_task_and_syncs_children(): void
    {
        $other = User::factory()->child()->create();
        $task = Task::factory()->create();
        $task->children()->attach($this->child);

        $this->actingAs($this->parent)->put(route('parent.tasks.update', $task), $this->payload([
            'title' => 'Baru', 'children' => [$other->id], 'active' => 0,
        ]))->assertRedirect(route('parent.tasks.index'));

        $task->refresh();
        $this->assertSame('Baru', $task->title);
        $this->assertFalse($task->active);
        $this->assertSame([$other->id], $task->children()->pluck('users.id')->all());
    }

    public function test_delete_is_soft(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($this->parent)->delete(route('parent.tasks.destroy', $task))
            ->assertRedirect(route('parent.tasks.index'));

        $this->assertSoftDeleted($task);
    }

    public function test_child_cannot_manage_tasks(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($this->child);

        $this->get(route('parent.tasks.index'))->assertForbidden();
        $this->post(route('parent.tasks.store'), $this->payload())->assertForbidden();
        $this->put(route('parent.tasks.update', $task), $this->payload())->assertForbidden();
        $this->delete(route('parent.tasks.destroy', $task))->assertForbidden();
        $this->assertNull(Task::first()->deleted_at);
    }

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->parent)->get(route('parent.tasks.create'))->assertOk()->assertSee('Budi');
    }
}
