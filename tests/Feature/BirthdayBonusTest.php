<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointsLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BirthdayBonusTest extends TestCase
{
    use RefreshDatabase;

    private function child(int $month, int $day, string $name = 'Ayu'): User
    {
        return User::factory()->child('1234', $month, $day)->create(['name' => $name]);
    }

    private function visit(User $child)
    {
        return $this->actingAs($child)->get(route('child.missions'));
    }

    private function balance(User $child): int
    {
        return app(PointsLedger::class)->balance($child);
    }

    public function test_bonus_is_given_once_even_after_many_visits(): void
    {
        $child = $this->child(11, 25);
        $this->travelTo('2026-11-25 09:00:00');

        foreach (range(1, 5) as $_) {
            $this->visit($child);
        }

        $this->assertSame(50, $this->balance($child));
        $this->assertSame(1, PointTransaction::where('type', LedgerType::Birthday)->count());
    }

    public function test_no_bonus_on_other_days(): void
    {
        $child = $this->child(11, 25);

        foreach (['2026-11-24 23:59:00', '2026-11-26 00:01:00'] as $now) {
            $this->travelTo($now);
            $this->visit($child);
        }

        $this->assertSame(0, $this->balance($child));
    }

    public function test_bonus_comes_again_next_year(): void
    {
        $child = $this->child(11, 25);
        $this->travelTo('2026-11-25 09:00:00');
        $this->visit($child);
        $this->travelTo('2027-11-25 09:00:00');
        $this->visit($child);

        $this->assertSame(100, $this->balance($child));
    }

    public function test_leap_day_child_is_celebrated_on_feb_28_in_common_years_and_feb_29_in_leap_years(): void
    {
        $child = $this->child(2, 29);

        $this->travelTo('2027-02-28 10:00:00');
        $this->visit($child);
        $this->assertSame(50, $this->balance($child));

        $this->travelTo('2027-03-01 10:00:00');
        $this->visit($child);
        $this->assertSame(50, $this->balance($child));

        $this->travelTo('2028-02-28 10:00:00'); // 2028 kabisat: belum waktunya
        $this->visit($child);
        $this->assertSame(50, $this->balance($child));

        $this->travelTo('2028-02-29 10:00:00');
        $this->visit($child);
        $this->assertSame(100, $this->balance($child));
    }

    public function test_day_boundary_uses_jakarta_time_not_utc(): void
    {
        $child = $this->child(11, 25);

        // 00:01 WIB tanggal 25 = 17:01 UTC tanggal 24.
        $this->travelTo(Carbon::parse('2026-11-25 00:01:00', 'Asia/Jakarta'));
        $this->visit($child);

        $this->assertSame(50, $this->balance($child));
    }

    public function test_other_children_and_parents_get_nothing(): void
    {
        $birthday = $this->child(11, 25);
        $sibling = $this->child(3, 3, 'Adik');
        $this->travelTo('2026-11-25 09:00:00');

        $this->visit($sibling);
        $this->actingAs(User::factory()->parent()->create())->get(route('parent.dashboard'));

        $this->assertSame(0, $this->balance($sibling));
        $this->assertSame(0, $this->balance($birthday));
    }

    public function test_celebration_banner_shows_only_on_the_awarding_visit(): void
    {
        $child = $this->child(11, 25);
        $this->travelTo('2026-11-25 09:00:00');

        $this->visit($child)->assertSee('Selamat ulang tahun');
        $this->visit($child)->assertDontSee('Selamat ulang tahun');
    }

    public function test_child_without_birthday_data_is_ignored(): void
    {
        $child = User::factory()->child()->create(['birthday_month' => null, 'birthday_day' => null]);
        $this->travelTo('2026-11-25 09:00:00');

        $this->visit($child)->assertOk();

        $this->assertSame(0, $this->balance($child));
    }
}
