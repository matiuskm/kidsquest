<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskScheduleTest extends TestCase
{
    use RefreshDatabase;

    private TaskSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedule = new TaskSchedule;
    }

    private function on(Task $task, string $date): bool
    {
        return $this->schedule->occursOn($task, CarbonImmutable::parse($date));
    }

    public function test_daily_occurs_every_day(): void
    {
        $task = Task::factory()->create();

        foreach (['2026-11-23', '2026-11-24', '2026-12-31'] as $d) {
            $this->assertTrue($this->on($task, $d), $d);
        }
    }

    public function test_weekly_occurs_only_on_chosen_weekdays(): void
    {
        $task = Task::factory()->weekly([1, 3])->create(); // Senin, Rabu

        $this->assertTrue($this->on($task, '2026-11-23')); // Senin
        $this->assertFalse($this->on($task, '2026-11-24'));
        $this->assertTrue($this->on($task, '2026-11-25')); // Rabu
        $this->assertFalse($this->on($task, '2026-11-29')); // Minggu
    }

    public function test_weekly_sunday_is_zero(): void
    {
        $task = Task::factory()->weekly([0])->create();

        $this->assertTrue($this->on($task, '2026-11-29'));
        $this->assertFalse($this->on($task, '2026-11-23'));
    }

    public function test_monthly_clamps_to_last_day_of_short_months(): void
    {
        $task = Task::factory()->monthly(31)->create();

        $this->assertTrue($this->on($task, '2027-02-28'));
        $this->assertFalse($this->on($task, '2027-02-27'));
        $this->assertTrue($this->on($task, '2028-02-29'));
        $this->assertFalse($this->on($task, '2028-02-28'));
        $this->assertTrue($this->on($task, '2026-04-30'));
        $this->assertFalse($this->on($task, '2026-04-29'));
        $this->assertTrue($this->on($task, '2026-01-31'));
        $this->assertFalse($this->on($task, '2026-01-30'));
    }

    public function test_monthly_normal_day(): void
    {
        $task = Task::factory()->monthly(15)->create();

        $this->assertTrue($this->on($task, '2026-11-15'));
        $this->assertFalse($this->on($task, '2026-11-16'));
    }

    public function test_respects_start_and_end_dates(): void
    {
        $task = Task::factory()->create(['start_date' => '2026-11-10', 'end_date' => '2026-11-20']);

        $this->assertFalse($this->on($task, '2026-11-09'));
        $this->assertTrue($this->on($task, '2026-11-10'));
        $this->assertTrue($this->on($task, '2026-11-20'));
        $this->assertFalse($this->on($task, '2026-11-21'));
    }

    public function test_inactive_task_never_occurs(): void
    {
        $task = Task::factory()->create(['active' => false]);

        $this->assertFalse($this->on($task, '2026-11-23'));
    }

    public function test_once_task_stays_due_until_end_date(): void
    {
        $task = Task::factory()->once()->create(['start_date' => '2026-11-10']);

        $this->assertFalse($this->on($task, '2026-11-09'));
        $this->assertTrue($this->on($task, '2026-11-10'));
        $this->assertTrue($this->on($task, '2026-12-25'));
    }

    public function test_due_for_returns_only_assigned_active_non_deleted_tasks(): void
    {
        $a = User::factory()->child()->create();
        $b = User::factory()->child()->create();
        $mine = Task::factory()->create();
        $mine->children()->attach($a);
        $theirs = Task::factory()->create();
        $theirs->children()->attach($b);
        $deleted = Task::factory()->create();
        $deleted->children()->attach($a);
        $deleted->delete();
        $notToday = Task::factory()->weekly([2])->create(); // Selasa
        $notToday->children()->attach($a);

        $due = $this->schedule->dueFor($a, CarbonImmutable::parse('2026-11-23')); // Senin

        $this->assertSame([$mine->id], $due->pluck('id')->all());
    }
}
