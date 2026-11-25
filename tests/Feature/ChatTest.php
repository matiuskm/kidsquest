<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\PushMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $budi;

    private User $sari;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->parent = User::factory()->parent()->create(['name' => 'Ayah']);
        $this->budi = User::factory()->child()->create(['name' => 'Budi']);
        $this->sari = User::factory()->child()->create(['name' => 'Sari']);
    }

    private function say(User $from, User $to, string $body = 'Halo'): void
    {
        $this->actingAs($from)->post(route('chat.store', $to), ['body' => $body]);
    }

    public function test_child_sends_message_to_sibling_and_gets_push(): void
    {
        $this->sari->updatePushSubscription('https://push.example/sari', 'key', 'token', 'aes128gcm');

        $this->actingAs($this->budi)->post(route('chat.store', $this->sari), ['body' => 'Main yuk'])->assertRedirect(route('chat.show', $this->sari));

        $m = Message::sole();
        $this->assertSame([$this->budi->id, $this->sari->id, 'Main yuk', null], [$m->sender_id, $m->recipient_id, $m->body, $m->read_at]);
        Notification::assertSentTo($this->sari, PushMessage::class);
    }

    public function test_child_and_parent_can_chat_both_ways(): void
    {
        $this->say($this->budi, $this->parent, 'Yah, boleh main?');
        $this->say($this->parent, $this->budi, 'Boleh');

        $this->assertSame(2, Message::count());
    }

    public function test_body_is_required_and_limited_to_500_chars(): void
    {
        $this->actingAs($this->budi)->post(route('chat.store', $this->sari), ['body' => '  '])->assertSessionHasErrors('body');
        $this->post(route('chat.store', $this->sari), ['body' => str_repeat('a', 501)])->assertSessionHasErrors('body');
        $this->post(route('chat.store', $this->sari), ['body' => str_repeat('a', 500)])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Message::count());
    }

    public function test_forbidden_pairs(): void
    {
        $other = User::factory()->parent()->create();
        $this->actingAs($this->budi)->post(route('chat.store', $this->budi), ['body' => 'x'])->assertForbidden();
        $this->actingAs($this->parent)->post(route('chat.store', $other), ['body' => 'x'])->assertForbidden();

        $this->sari->update(['peer_chat' => false]);
        $this->actingAs($this->budi)->post(route('chat.store', $this->sari), ['body' => 'x'])->assertForbidden();
        $this->actingAs($this->sari)->post(route('chat.store', $this->parent), ['body' => 'x'])->assertRedirect();

        $this->assertSame(1, Message::count());
    }

    public function test_parent_sees_only_own_conversations_not_between_children(): void
    {
        $this->say($this->budi, $this->sari, 'Rahasia kakak adik');
        $this->say($this->budi, $this->parent, 'Pesan untuk ayah');

        $this->actingAs($this->parent)->get(route('chat.show', $this->budi))
            ->assertOk()->assertSee('Pesan untuk ayah')->assertDontSee('Rahasia kakak adik');
        $this->get(route('chat.index'))->assertDontSee('Rahasia kakak adik');
    }

    public function test_open_thread_marks_incoming_read_and_shows_history(): void
    {
        $this->say($this->budi, $this->sari, 'Satu');
        $this->say($this->sari, $this->budi, 'Dua');

        $this->actingAs($this->sari)->get(route('chat.show', $this->budi))->assertSee('Satu')->assertSee('Dua');

        $this->assertNotNull(Message::where('body', 'Satu')->first()->read_at);
        $this->assertNull(Message::where('body', 'Dua')->first()->read_at);
    }

    public function test_index_lists_contacts_with_unread_count_and_nav_badge(): void
    {
        $this->say($this->budi, $this->sari);
        $this->say($this->budi, $this->sari);
        $this->say($this->parent, $this->sari);

        $this->actingAs($this->sari)->get(route('chat.index'))
            ->assertOk()->assertSee('Budi')->assertSee('Ayah')->assertSee('data-unread="3"', false);
    }

    public function test_poll_returns_only_new_messages_and_marks_them_read(): void
    {
        $this->say($this->budi, $this->sari, 'Lama');
        $first = Message::first();
        $this->say($this->budi, $this->sari, 'Baru');
        $this->say($this->parent, $this->sari, 'Bukan thread ini');

        $this->actingAs($this->sari)->getJson(route('chat.poll', ['user' => $this->budi, 'after' => $first->id]))
            ->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Baru')->assertJsonPath('messages.0.mine', false);

        $this->assertNotNull(Message::where('body', 'Baru')->first()->read_at);
        $this->assertNull(Message::where('body', 'Bukan thread ini')->first()->read_at);
    }

    public function test_parent_can_switch_off_chat_between_children(): void
    {
        $this->actingAs($this->parent)->post(route('parent.children.chat', $this->sari))->assertRedirect();
        $this->assertFalse($this->sari->refresh()->peer_chat);

        $this->post(route('parent.children.chat', $this->sari));
        $this->assertTrue($this->sari->refresh()->peer_chat);

        $this->actingAs($this->budi)->post(route('parent.children.chat', $this->sari))->assertForbidden();
    }

    public function test_removing_a_child_deletes_their_messages(): void
    {
        $this->say($this->budi, $this->sari);
        $this->say($this->parent, $this->budi);
        $this->say($this->sari, $this->parent);

        app(\App\Services\ChildService::class)->remove($this->budi);

        $this->assertSame(['Sari' => 1], Message::with('sender')->get()->mapWithKeys(fn ($m) => [$m->sender->name => 1])->all());
    }

    public function test_guests_are_sent_away(): void
    {
        $this->get(route('chat.index'))->assertRedirect();
    }
}
