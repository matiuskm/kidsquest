<?php

namespace App\Support;

use App\Enums\Recurrence;
use App\Models\Task;

final class Labels
{
    public const WEEKDAYS = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    public static function recurrence(Task $task): string
    {
        return match ($task->recurrence) {
            Recurrence::Once => __('Sekali'),
            Recurrence::Daily => __('Setiap hari'),
            Recurrence::Weekly => __('Tiap').' '.collect($task->weekdays)->map(fn ($d) => __(self::WEEKDAYS[$d]))->join(', '),
            Recurrence::Monthly => __('Tiap tanggal :n', ['n' => $task->month_day]),
        };
    }
}
