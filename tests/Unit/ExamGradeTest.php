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
