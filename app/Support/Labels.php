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
            Recurrence::Once => 'Sekali',
            Recurrence::Daily => 'Setiap hari',
            Recurrence::Weekly => 'Tiap '.collect($task->weekdays)->map(fn ($d) => self::WEEKDAYS[$d])->join(', '),
            Recurrence::Monthly => "Tiap tanggal {$task->month_day}",
        };
    }
}
