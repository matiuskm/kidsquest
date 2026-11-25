<?php

namespace App\Support;

use InvalidArgumentException;

/** Rentang nilai ulangan → huruf dan poin, dibaca dari config('kidsquest.exam_grades'). */
final readonly class ExamGrade
{
    private function __construct(public string $letter, public int $points) {}

    public static function for(int $score): self
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException('Nilai harus 0-100.');
        }

        foreach (config('kidsquest.exam_grades') as [$min, $letter, $points]) {
            if ($score >= $min) {
                return new self($letter, $points);
            }
        }

        throw new InvalidArgumentException('Config exam_grades harus punya batas bawah 0.');
    }
}
