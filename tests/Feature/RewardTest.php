<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Exceptions\InsufficientPoints;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\User;
use App\Services\PointsLedger;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    private Reward $reward;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parent = User::factory()->parent()->create();
        $this->child = User::factory()->child()->create();
        $this->reward = Reward::factory()->create(['name' => 'Es krim', 'cost' => 60]);
    }

    private function fund(int $points): void
    {
        app(PointsLedger::class)->earn($this->child, $points, $this->child);
    }

    private function balance(): int
    {
        return app(PointsLedger::class)->balance($this->child);
    }

    private function claim(?Reward $reward = null)
    {
        return $this->actingAs($this->child)->post(route('child.claim', $reward ?? $this->reward));
    }

    public function test_claim_deducts_points_immediately(): void
    {
        $this->fund(100);

        $this->claim()->assertRedirect(route('child.rewards'));

        $this->assertSame(40, $this->balance());
        $r = Redemption::sole();
        $this->assertSame([ReviewStatus::Pending, 60], [$r->status, $r->cost]);
    }

    public function test_claim_with_insufficient_points_is_refused(): void
    {
        $this->fund(59);

        $this->claim()->assertSessionHasErrors('reward');

        $this->assertSame(59, $this->balance());
        $this->assertSame(0, Redemption::count());
    }

    public function test_second_claim_cannot_overdraw(): void
    {
        $this->fund(100);

        $this->claim();
        $this->claim()->assertSessionHasErrors('reward');

        $this->assertSame(1, Redemption::count());
        $this->assertSame(40, $this->balance());
    }

    public function test_reward_can_be_claimed_repeatedly_while_points_last(): void
    {
        $this->fund(120);
        $this->claim();
        $this->travel(11)->seconds();
        $this->claim()->assertSessionHasNoErrors();

        $this->assertSame(0, $this->balance());
        $this->assertSame(2, Redemption::count());
    }

    public function test_double_tap_on_claim_creates_a_single_claim(): void
    {
        $this->fund(200);

        $this->claim()->assertSessionHasNoErrors();
        $this->claim()->assertSessionHasErrors('reward');

        $this->assertSame(1, Redemption::count());
        $this->assertSame(140, $this->balance());
    }

    public function test_reject_refunds_exactly_once_using_the_snapshot_cost(): void
    {
        $this->fund(100);
        $this->claim();
        $this->reward->update(['cost' => 999]);
        $r = Redemption::sole();

        $this->actingAs($this->parent)->post(route('parent.redemptions.reject', $r), ['note' => 'Belum waktunya']);
        $this->post(route('parent.redemptions.reject', $r))->assertSessionHasErrors('review');

        $this->assertSame(100, $this->balance());
        $this->assertSame(ReviewStatus::Rejected, $r->fresh()->status);
    }

    public function test_approve_only_changes_status(): void
    {
        $this->fund(100);
        $this->claim();
        $r = Redemption::sole();

        $this->actingAs($this->parent)->post(route('parent.redemptions.approve', $r))->assertSessionHasNoErrors();
        $this->post(route('parent.redemptions.reject', $r))->assertSessionHasErrors('review');

        $this->assertSame(ReviewStatus::Approved, $r->fresh()->status);
        $this->assertSame(40, $this->balance());
    }

    public function test_pending_claim_of_a_soft_deleted_reward_can_still_be_decided(): void
    {
        $this->fund(100);
        $this->claim();
        $this->reward->delete();
        $r = Redemption::sole();

        $this->actingAs($this->parent)->get(route('parent.dashboard'))->assertOk()->assertSee('Es krim');
        $this->post(route('parent.redemptions.reject', $r))->assertSessionHasNoErrors();

        $this->assertSame(100, $this->balance());
    }

    public function test_inactive_and_deleted_rewards_cannot_be_claimed_or_listed(): void
    {
        $this->fund(100);
        $inactive = Reward::factory()->create(['name' => 'Nonaktif', 'active' => false]);
        $gone = Reward::factory()->create(['name' => 'Terhapus']);
        $gone->delete();

        $this->actingAs($this->child)->get(route('child.rewards'))->assertOk()
            ->assertSee('Es krim')->assertDontSee('Nonaktif')->assertDontSee('Terhapus');
        $this->claim($inactive)->assertSessionHasErrors('reward');
        $this->claim($gone)->assertNotFound();
        $this->assertSame(100, $this->balance());
    }

    public function test_child_only_sees_own_claims_and_cannot_decide(): void
    {
        $this->fund(100);
        $this->claim();
        $other = User::factory()->child()->create();
        $r = Redemption::sole();

        $this->actingAs($other)->get(route('child.rewards'))->assertOk()->assertDontSee('Menunggu');
        $this->post(route('parent.redemptions.approve', $r))->assertForbidden();
        $this->post(route('parent.redemptions.reject', $r))->assertForbidden();
        $this->assertSame(ReviewStatus::Pending, $r->fresh()->status);
    }

    public function test_service_throws_when_points_are_short(): void
    {
        $this->expectException(InsufficientPoints::class);
        app(RewardService::class)->claim($this->child, $this->reward);
    }

    public function test_parent_reward_crud_is_soft_delete(): void
    {
        $this->actingAs($this->parent);
        $data = ['name' => 'Main game 1 jam', 'cost' => 80, 'icon' => '🎮', 'active' => 1];

        $this->post(route('parent.rewards.store'), $data)->assertRedirect(route('parent.rewards.index'));
        $reward = Reward::where('name', 'Main game 1 jam')->firstOrFail();

        $this->put(route('parent.rewards.update', $reward), [...$data, 'cost' => 90])->assertRedirect(route('parent.rewards.index'));
        $this->assertSame(90, $reward->fresh()->cost);

        $this->delete(route('parent.rewards.destroy', $reward))->assertRedirect(route('parent.rewards.index'));
        $this->assertSoftDeleted($reward);
    }

    public function test_reward_validation_and_authorization(): void
    {
        $this->actingAs($this->parent)->post(route('parent.rewards.store'), ['name' => 'X', 'cost' => 0])->assertSessionHasErrors('cost');
        $this->post(route('parent.rewards.store'), ['name' => '', 'cost' => 5])->assertSessionHasErrors('name');

        $this->actingAs($this->child)->get(route('parent.rewards.index'))->assertForbidden();
        $this->post(route('parent.rewards.store'), ['name' => 'X', 'cost' => 5])->assertForbidden();
    }

    public function test_parent_can_adjust_points_with_a_note(): void
    {
        $this->fund(10);
        $this->actingAs($this->parent)->post(route('parent.points.adjust', $this->child), ['amount' => 15, 'note' => 'Bonus rajin'])
            ->assertSessionHasNoErrors();
        $this->post(route('parent.points.adjust', $this->child), ['amount' => -5, 'note' => 'Koreksi'])->assertSessionHasNoErrors();

        $this->assertSame(20, $this->balance());
    }

    public function test_adjust_cannot_go_below_zero_or_be_zero_and_note_is_optional(): void
    {
        $this->fund(10);
        $this->actingAs($this->parent);

        $this->post(route('parent.points.adjust', $this->child), ['amount' => -11, 'note' => 'x'])->assertSessionHasErrors('review');
        $this->post(route('parent.points.adjust', $this->child), ['amount' => 5])->assertSessionHasNoErrors();
        $this->post(route('parent.points.adjust', $this->child), ['amount' => 0, 'note' => 'x'])->assertSessionHasErrors('amount');
        $this->assertSame(15, $this->balance());

        $this->actingAs($this->child)->post(route('parent.points.adjust', $this->child), ['amount' => 50, 'note' => 'curang'])->assertForbidden();
        $this->assertSame(15, $this->balance());
    }
}
