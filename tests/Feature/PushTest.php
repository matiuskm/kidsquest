<?php

namespace Tests\Feature;

use App\Models\Reward;
use App\Models\Submission;
use App\Models\Task;
use App\Models\Redemption;
use App\Models\User;
use App\Notifications\PushMessage;
use App\Services\PointsLedger;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\PushSubscription;
use Tests\TestCase;

class PushTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->parent = User::factory()->parent()->create();
        $this->child = User::factory()->child()->create(['name' => 'Budi']);
        $this->subscribe($this->parent, 'https://push.example/parent');
        $this->subscribe($this->child, 'https://push.example/child');
    }

    private function subscribe(User $user, string $endpoint): void
    {
        $user->updatePushSubscription($endpoint, 'key', 'token', 'aes128gcm');
    }

    private function payload(string $endpoint = 'https://fcm.googleapis.com/new'): array
    {
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'pk', 'auth' => 'tk'], 'contentEncoding' => 'aes128gcm'];
    }

    public function test_subscribe_stores_subscription_for_the_logged_in_user(): void
    {
        $this->actingAs($this->child)->postJson(route('push.subscribe'), $this->payload())->assertNoContent();

        $this->assertSame($this->child->id, PushSubscription::where('endpoint', 'https://fcm.googleapis.com/new')->sole()->subscribable_id);
    }

    public function test_subscribe_validates_input_and_requires_login(): void
    {
        $this->postJson(route('push.subscribe'), $this->payload())->assertUnauthorized();

        $this->actingAs($this->child);
        $this->postJson(route('push.subscribe'), ['endpoint' => 'not-a-url', 'keys' => ['p256dh' => 'a', 'auth' => 'b']])->assertUnprocessable();
        $this->postJson(route('push.subscribe'), ['endpoint' => 'https://push.example/x'])->assertUnprocessable();
        $this->postJson(route('push.subscribe'), $this->payload('http://insecure.example/x'))->assertUnprocessable();
    }

    public function test_unsubscribe_only_removes_own_subscription(): void
    {
        $this->actingAs($this->child)->deleteJson(route('push.unsubscribe'), ['endpoint' => 'https://push.example/parent'])->assertNoContent();
        $this->assertSame(1, PushSubscription::where('endpoint', 'https://push.example/parent')->count());

        $this->deleteJson(route('push.unsubscribe'), ['endpoint' => 'https://push.example/child'])->assertNoContent();
        $this->assertSame(0, PushSubscription::where('endpoint', 'https://push.example/child')->count());
    }

    public function test_logout_removes_the_device_subscription(): void
    {
        $this->actingAs($this->child)->post(route('logout'), ['push_endpoint' => 'https://push.example/child'])
            ->assertRedirect(route('child.pick'));

        $this->assertSame(0, PushSubscription::where('endpoint', 'https://push.example/child')->count());
    }

    public function test_child_submit_notifies_the_parent(): void
    {
        $task = Task::factory()->create(['title' => 'Sikat gigi']);
        $task->children()->attach($this->child);

        $this->actingAs($this->child)->post(route('child.submit', $task), ['photo' => UploadedFile::fake()->image('a.jpg')])
            ->assertSessionHas('sound', 'submit');

        Notification::assertSentTo($this->parent, PushMessage::class, fn ($n) => str_contains($n->body, 'Budi') && str_contains($n->body, 'Sikat gigi'));
        Notification::assertNotSentTo($this->child, PushMessage::class);
    }

    public function test_review_decisions_notify_the_child(): void
    {
        Storage::disk('local')->put('photos/a.webp', 'x');
        $task = Task::factory()->create(['points' => 7]);
        $ok = Submission::factory()->create(['task_id' => $task->id, 'child_id' => $this->child->id, 'photo_path' => 'photos/a.webp']);
        $no = Submission::factory()->create(['task_id' => $task->id, 'child_id' => $this->child->id, 'period_date' => '2026-11-24']);

        $this->actingAs($this->parent)->post(route('parent.submissions.approve', $ok));
        Notification::assertSentToTimes($this->child, PushMessage::class, 1);
        Notification::assertSentTo($this->child, PushMessage::class, fn ($n) => str_contains($n->body, '7'));

        $this->post(route('parent.submissions.reject', $no), ['note' => 'Buram']);
        Notification::assertSentToTimes($this->child, PushMessage::class, 2);
        Notification::assertNotSentTo($this->parent, PushMessage::class);
    }

    public function test_claim_notifies_parent_and_decision_notifies_child(): void
    {
        app(PointsLedger::class)->earn($this->child, 100, $this->child);
        $reward = Reward::factory()->create(['name' => 'Es krim', 'cost' => 60]);

        $this->actingAs($this->child)->post(route('child.claim', $reward))->assertSessionHas('sound', 'redeem');
        Notification::assertSentTo($this->parent, PushMessage::class, fn ($n) => str_contains($n->body, 'Es krim'));

        $this->actingAs($this->parent)->post(route('parent.redemptions.approve', Redemption::sole()));
        Notification::assertSentToTimes($this->child, PushMessage::class, 1);
    }

    public function test_user_without_subscription_is_skipped(): void
    {
        PushSubscription::query()->delete();
        $task = Task::factory()->create();
        $task->children()->attach($this->child);

        $this->actingAs($this->child)->post(route('child.submit', $task), ['photo' => UploadedFile::fake()->image('a.jpg')]);

        Notification::assertNothingSent();
    }

    public function test_a_failing_push_never_breaks_the_request(): void
    {
        $this->app->instance(Dispatcher::class, new class implements Dispatcher
        {
            public function send($notifiables, $notification): void
            {
                throw new \RuntimeException('push down');
            }

            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                throw new \RuntimeException('push down');
            }
        });
        $task = Task::factory()->create();
        $task->children()->attach($this->child);

        $this->actingAs($this->child)->post(route('child.submit', $task), ['photo' => UploadedFile::fake()->image('a.jpg')])
            ->assertRedirect(route('child.missions'))->assertSessionHasNoErrors();

        $this->assertSame(1, Submission::count());
    }

    public function test_deleting_a_child_purges_push_subscriptions(): void
    {
        $this->actingAs($this->parent)->delete(route('parent.children.destroy', $this->child));

        $this->assertSame(0, PushSubscription::where('endpoint', 'https://push.example/child')->count());
    }
}
