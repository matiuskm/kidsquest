<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\Submission;
use App\Models\User;
use App\Services\PointsLedger;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChildManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->parent = User::factory()->parent()->create();
    }

    private function payload(array $o = []): array
    {
        return array_merge(['name' => 'Citra', 'avatar' => '🦊', 'pin' => '4321', 'birthday_month' => 7, 'birthday_day' => 9], $o);
    }

    public function test_parent_creates_child_with_hashed_pin_and_birthday(): void
    {
        $this->actingAs($this->parent)->post(route('parent.children.store'), $this->payload())
            ->assertRedirect(route('parent.children.index'));

        $child = User::children()->firstOrFail();
        $this->assertTrue($child->isChild());
        $this->assertTrue(Hash::check('4321', $child->pin_hash));
        $this->assertNotSame('4321', $child->pin_hash);
        $this->assertSame([7, 9], [$child->birthday_month, $child->birthday_day]);
        $this->assertNull($child->email);
    }

    public function test_create_validation(): void
    {
        $bad = [
            ['pin' => '12'], ['pin' => 'abcd'], ['pin' => ''], ['name' => ''], ['avatar' => 'x'],
            ['birthday_month' => 13], ['birthday_month' => 2, 'birthday_day' => 30], ['birthday_month' => 4, 'birthday_day' => 31],
            ['birthday_month' => null], ['birthday_day' => null],
        ];

        foreach ($bad as $i => $o) {
            $this->actingAs($this->parent)->post(route('parent.children.store'), $this->payload($o))->assertSessionHasErrors(null, null, "case $i");
        }
        $this->assertSame(0, User::children()->count());

        $this->post(route('parent.children.store'), $this->payload(['birthday_month' => 2, 'birthday_day' => 29]))->assertSessionHasNoErrors();
    }

    public function test_parent_updates_name_and_avatar_but_birthday_is_locked(): void
    {
        $child = User::factory()->child('1111', 5, 5)->create();

        $this->actingAs($this->parent)->put(route('parent.children.update', $child), [
            'name' => 'Baru', 'avatar' => '🐼', 'birthday_month' => 1, 'birthday_day' => 1,
        ])->assertRedirect(route('parent.children.index'));

        $child->refresh();
        $this->assertSame(['Baru', '🐼', 5, 5], [$child->name, $child->avatar, $child->birthday_month, $child->birthday_day]);
        $this->assertTrue(Hash::check('1111', $child->pin_hash));
    }

    public function test_model_refuses_birthday_change_once_set(): void
    {
        $child = User::factory()->child('1111', 5, 5)->create();

        $this->expectException(\LogicException::class);
        $child->update(['birthday_month' => 6]);
    }

    public function test_parent_resets_pin(): void
    {
        $child = User::factory()->child('1111')->create();

        $this->actingAs($this->parent)->put(route('parent.children.update', $child), ['name' => $child->name, 'avatar' => '🦊', 'pin' => '9999']);

        $this->assertTrue(Hash::check('9999', $child->fresh()->pin_hash));
    }

    public function test_parent_pin_reset_clears_a_lockout(): void
    {
        $child = User::factory()->child('1111')->create();
        foreach (range(1, 5) as $_) {
            $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '0000']);
        }

        $this->actingAs($this->parent)->put(route('parent.children.update', $child), ['name' => $child->name, 'avatar' => '🦊', 'pin' => '7777']);
        $this->post(route('logout'));

        $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '7777'])->assertRedirect(route('child.missions'));
    }

    public function test_child_edits_own_profile(): void
    {
        $child = User::factory()->child('1111', 5, 5)->create();

        $this->actingAs($child)->put(route('child.profile.update'), [
            'name' => 'Kiki', 'avatar' => '🐯', 'birthday_month' => 1, 'birthday_day' => 1, 'role' => 'parent',
        ])->assertSessionHasNoErrors();

        $child->refresh();
        $this->assertSame(['Kiki', '🐯', 5, 5], [$child->name, $child->avatar, $child->birthday_month, $child->birthday_day]);
        $this->assertTrue($child->isChild());
    }

    public function test_child_changes_pin_only_with_correct_current_pin(): void
    {
        $child = User::factory()->child('1111')->create();
        $base = ['name' => $child->name, 'avatar' => $child->avatar];

        $this->actingAs($child)->put(route('child.profile.update'), [...$base, 'current_pin' => '0000', 'pin' => '2222'])
            ->assertSessionHasErrors('current_pin');
        $this->assertTrue(Hash::check('1111', $child->fresh()->pin_hash));

        $this->put(route('child.profile.update'), [...$base, 'current_pin' => '1111', 'pin' => '12'])->assertSessionHasErrors('pin');

        $this->put(route('child.profile.update'), [...$base, 'current_pin' => '1111', 'pin' => '2222'])->assertSessionHasNoErrors();
        $fresh = $child->fresh();
        $this->assertTrue(Hash::check('2222', $fresh->pin_hash));
        $this->assertFalse(Hash::check('1111', $fresh->pin_hash));
    }

    public function test_child_cannot_manage_children(): void
    {
        $child = User::factory()->child()->create();
        $other = User::factory()->child()->create();
        $this->actingAs($child);

        $this->get(route('parent.children.index'))->assertForbidden();
        $this->post(route('parent.children.store'), $this->payload())->assertForbidden();
        $this->put(route('parent.children.update', $other), ['name' => 'x', 'avatar' => '🦊'])->assertForbidden();
        $this->delete(route('parent.children.destroy', $other))->assertForbidden();
    }

    public function test_deleting_child_purges_photos_settles_pending_and_keeps_ledger(): void
    {
        $child = User::factory()->child()->create();
        $ledger = app(PointsLedger::class);
        $ledger->earn($child, 100, $child);
        $reward = Reward::factory()->create(['cost' => 60]);
        app(RewardService::class)->claim($child, $reward);
        Storage::disk('local')->put('photos/a.webp', 'x');
        $pending = Submission::factory()->create(['child_id' => $child->id, 'photo_path' => 'photos/a.webp']);

        $this->actingAs($this->parent)->delete(route('parent.children.destroy', $child))->assertRedirect(route('parent.children.index'));

        $this->assertSoftDeleted($child);
        Storage::disk('local')->assertMissing('photos/a.webp');
        $this->assertNull($pending->fresh()->photo_path);
        $this->assertSame(ReviewStatus::Rejected, $pending->fresh()->status);
        $this->assertSame(ReviewStatus::Rejected, Redemption::sole()->status);
        $this->assertSame(3, PointTransaction::where('user_id', $child->id)->count());
        $this->assertSame(100, $ledger->balance($child));
        $this->post(route('logout'));
        $this->get(route('child.pick'))->assertDontSee($child->name);
    }
}
