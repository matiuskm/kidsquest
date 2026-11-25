<?php

namespace Tests\Unit;

use App\Support\ExamGrade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamGradeTest extends TestCase
{
    public static function bands(): array
    {
        return [
            [0, 'D', 0], [69, 'D', 0], [70, 'C', 3], [79, 'C', 3],
            [80, 'B', 4], [89, 'B', 4], [90, 'A', 5], [100, 'A', 5],
        ];
    }

    #[DataProvider('bands')]
    public function test_score_maps_to_letter_and_points(int $score, string $letter, int $points): void
    {
        $grade = ExamGrade::for($score);

        $this->assertSame($letter, $grade->letter);
        $this->assertSame($points, $grade->points);
    }

    public static function letters(): array
    {
        return [['A', 5], ['B', 4], ['C', 3], ['D', 0], ['E', 0], ['b', 4], ['B+', 4], ['A-', 5], ['d-', 0]];
    }

    #[DataProvider('letters')]
    public function test_letter_maps_to_points(string $letter, int $points): void
    {
        $grade = ExamGrade::forLetter($letter);

        $this->assertSame(strtoupper($letter), $grade->letter);
        $this->assertSame($points, $grade->points);
    }

    public function test_unknown_letter_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExamGrade::forLetter('F');
    }

    public function test_double_modifier_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExamGrade::forLetter('B++');
    }

    #[DataProvider('outOfRange')]
    public function test_out_of_range_score_is_rejected(int $score): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExamGrade::for($score);
    }

    public static function outOfRange(): array
    {
        return [[-1], [101]];
    }
}
