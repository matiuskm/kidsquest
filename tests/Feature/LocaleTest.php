<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\PointTransaction;
use App\Models\User;
use App\Notifications\PushMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_switches_language_and_pages_follow(): void
    {
        $child = User::factory()->child()->create();

        $this->actingAs($child)->get(route('child.missions'))->assertSee('Misi hari ini');

        $this->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();
        $this->assertSame('en', $child->refresh()->locale);
        $this->get(route('child.missions'))->assertSee("Today&#039;s missions", false)->assertSee('Log out')->assertDontSee('Misi hari ini');
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post(route('locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
        $this->assertSame('id', $parent->refresh()->locale);
    }

    public function test_parent_pages_and_validation_messages_are_translated(): void
    {
        $parent = User::factory()->parent()->create(['locale' => 'en']);

        $this->actingAs($parent)->get(route('parent.dashboard'))->assertSee('To review')->assertSee('Hello,');
        $this->post(route('parent.tasks.store'), [])->assertSessionHasErrors('title');
        $this->assertStringContainsString('is required', session('errors')->first('title'));
    }

    public function test_indonesian_validation_message_is_readable_not_a_key(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post(route('parent.tasks.store'), [])->assertSessionHasErrors('title');

        $this->assertStringContainsString('wajib diisi', session('errors')->first('title'));
    }

    public function test_push_is_written_in_the_recipients_language(): void
    {
        Notification::fake();
        $child = User::factory()->child()->create(['locale' => 'en']);
        $child->updatePushSubscription('https://push.example/c', 'key', 'token', 'aes128gcm');
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post(route('parent.points.adjust', $child), ['amount' => 10])->assertSessionHasNoErrors();

        Notification::assertSentTo($child, PushMessage::class, fn ($n) => $n->title === 'Bonus from your parents 🎁' && str_contains($n->body, '+10 points'));
    }

    public function test_parent_gives_free_points_without_a_reason(): void
    {
        $child = User::factory()->child()->create();

        $this->actingAs(User::factory()->parent()->create())->post(route('parent.points.adjust', $child), ['amount' => 20])->assertSessionHasNoErrors();

        $this->assertSame('Bonus dari orang tua', PointTransaction::sole()->note);
        $this->assertSame(20, $child->points);
    }

    public function test_children_list_has_a_give_points_form(): void
    {
        $child = User::factory()->child()->create();

        $this->actingAs(User::factory()->parent()->create())->get(route('parent.children.index'))
            ->assertSee(route('parent.points.adjust', $child), false)->assertSee('Kasih poin');
    }
}
