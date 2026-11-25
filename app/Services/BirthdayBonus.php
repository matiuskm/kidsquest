<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

/** Bonus ulang tahun sekali per tahun, tanpa cron: diberikan saat anak pertama membuka app di hari itu. */
final class BirthdayBonus
{
    public function __construct(private PointsLedger $ledger) {}

    /** @return bool true bila bonus baru diberikan */
    public function award(User $child, CarbonImmutable $today): bool
    {
        if (! $this->isBirthday($child, $today)) {
            return false;
        }

        return $this->ledger->birthday($child, $today->year, config('kidsquest.birthday_bonus')) !== null;
    }

    private function isBirthday(User $child, CarbonImmutable $today): bool
    {
        if (! $child->birthday_month || ! $child->birthday_day) {
            return false;
        }

        $day = $child->birthday_day;

        // Lahir 29 Feb dirayakan 28 Feb di tahun non-kabisat.
        if ($child->birthday_month === 2 && $day === 29 && ! $today->isLeapYear()) {
            $day = 28;
        }

        return $today->month === $child->birthday_month && $today->day === $day;
    }
}
