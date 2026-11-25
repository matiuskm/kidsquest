<?php

namespace Tests\Unit;

use App\Enums\LedgerType;
use App\Exceptions\InsufficientPoints;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointsLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsLedgerTest extends TestCase
{
    use RefreshDatabase;

    private PointsLedger $ledger;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = new PointsLedger;
        $this->child = User::factory()->child()->create();
    }

    public function test_exam_writes_positive_exam_row_and_rejects_zero(): void
    {
        $tx = $this->ledger->exam($this->child, 4, $this->child);

        $this->assertSame(LedgerType::Exam, $tx->type);
        $this->assertSame(4, $this->ledger->balance($this->child));

        $this->expectException(\InvalidArgumentException::class);
        $this->ledger->exam($this->child, 0, $this->child);
    }

    public function test_balance_is_sum_of_ledger(): void
    {
        $this->ledger->earn($this->child, 30, $this->child);
        $this->ledger->earn($this->child, 20, $this->child);

        $this->assertSame(50, $this->ledger->balance($this->child));
        $this->assertSame(0, $this->ledger->balance(User::factory()->child()->create()));
    }

    public function test_redeem_reduces_balance_with_negative_row(): void
    {
        $this->ledger->earn($this->child, 100, $this->child);
        $tx = $this->ledger->redeem($this->child, 40, $this->child);

        $this->assertSame(-40, $tx->amount);
        $this->assertSame(LedgerType::Redeem, $tx->type);
        $this->assertSame(60, $this->ledger->balance($this->child));
    }

    public function test_redeem_more_than_balance_throws_and_writes_nothing(): void
    {
        $this->ledger->earn($this->child, 10, $this->child);

        try {
            $this->ledger->redeem($this->child, 11, $this->child);
            $this->fail('expected InsufficientPoints');
        } catch (InsufficientPoints) {
        }

        $this->assertSame(10, $this->ledger->balance($this->child));
        $this->assertSame(1, PointTransaction::count());
    }

    public function test_adjust_cannot_go_negative(): void
    {
        $this->ledger->earn($this->child, 10, $this->child);

        $this->expectException(InsufficientPoints::class);
        $this->ledger->adjust($this->child, -11, 'koreksi');
    }

    public function test_adjust_negative_within_balance_ok_and_keeps_note(): void
    {
        $this->ledger->earn($this->child, 10, $this->child);
        $tx = $this->ledger->adjust($this->child, -4, 'salah input');

        $this->assertSame(6, $this->ledger->balance($this->child));
        $this->assertSame('salah input', $tx->note);
        $this->assertSame(LedgerType::Adjust, $tx->type);
    }

    public function test_refund_restores_points(): void
    {
        $this->ledger->earn($this->child, 50, $this->child);
        $this->ledger->redeem($this->child, 50, $this->child);
        $this->ledger->refund($this->child, 50, $this->child);

        $this->assertSame(50, $this->ledger->balance($this->child));
    }

    public function test_birthday_twice_same_year_returns_null_and_single_row(): void
    {
        $first = $this->ledger->birthday($this->child, 2026, 50);
        $second = $this->ledger->birthday($this->child, 2026, 50);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, PointTransaction::where('type', LedgerType::Birthday)->count());
        $this->assertSame(50, $this->ledger->balance($this->child));
        $this->assertNotNull($this->ledger->birthday($this->child, 2027, 50));
    }

    public function test_non_positive_amounts_are_rejected_for_earn_redeem_refund(): void
    {
        foreach (['earn', 'redeem', 'refund'] as $method) {
            try {
                $this->ledger->$method($this->child, 0, $this->child);
                $this->fail("$method accepted 0");
            } catch (\InvalidArgumentException) {
            }
        }
        $this->assertSame(0, PointTransaction::count());
    }
}
