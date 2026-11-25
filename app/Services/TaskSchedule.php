<?php

namespace App\Services;

use App\Enums\Recurrence;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Menghitung kapan tugas jatuh tempo; tanpa cron. Status selesai/hangus diputuskan MissionService. */
final class TaskSchedule
{
    public function occursOn(Task $task, CarbonImmutable $date): bool
    {
        $day = $date->toDateString();

        if (! $task->active || $day < $task->start_date->toDateString()) {
            return false;
        }

        if ($task->end_date && $day > $task->end_date->toDateString()) {
            return false;
        }

        return match ($task->recurrence) {
            Recurrence::Once, Recurrence::Daily => true,
            Recurrence::Weekly => in_array($date->dayOfWeek, $task->weekdays ?? [], true),
            Recurrence::Monthly => $date->day === min($task->month_day, $date->daysInMonth),
        };
    }

    /** Kunci periode submission: tugas sekali = start_date (satu kali seumur hidup), lainnya = tanggal kejadian. */
    public function periodFor(Task $task, CarbonImmutable $date): CarbonImmutable
    {
        return $task->recurrence === Recurrence::Once ? CarbonImmutable::parse($task->start_date) : $date->startOfDay();
    }

    /** @return Collection<int, Task> */
    public function dueFor(User $child, CarbonImmutable $date): Collection
    {
        return $child->tasks()->where('active', true)->get()
            ->filter(fn (Task $task) => $this->occursOn($task, $date))
            ->values();
    }
}
