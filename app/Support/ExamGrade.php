<?php

namespace App\Support;

use InvalidArgumentException;

/** Rentang nilai ulangan → huruf dan poin, dibaca dari config('kidsquest.exam_grades'). */
final readonly class ExamGrade
{
    private function __construct(public string $letter, public int $points) {}

    /** Huruf yang dipilih anak/parent: A+ ... E-. Plus/minus tidak mengubah poin. */
    public const LETTERS = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'E+', 'E', 'E-'];

    /** Penilaian huruf: poin dari config; huruf yang tidak ada di config (E) tidak dapat poin. */
    public static function forLetter(string $letter): self
    {
        $letter = strtoupper($letter);

        if (! in_array($letter, self::LETTERS, true)) {
            throw new InvalidArgumentException('Huruf harus A sampai E (boleh + atau -).');
        }

        $base = rtrim($letter, '+-');
        $band = collect(config('kidsquest.exam_grades'))->first(fn ($b) => $b[1] === $base);

        return new self($letter, $band[2] ?? 0);
    }

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
