<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\User;
use App\Notifications\PushMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\PushSubscription;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->parent = User::factory()->parent()->create(['email' => 'kris@example.test', 'password' => 'secret-pass']);
        $this->child = User::factory()->child('4826')->create(['name' => 'Budi']);
    }

    private function pin(string $pin, array $extra = [])
    {
        return $this->withCookie('kq_family', '1')->post(route('child.pin'), ['child_id' => $this->child->id, 'pin' => $pin] + $extra);
    }

    private function lockChild(): void
    {
        foreach (range(1, 5) as $_) {
            $this->pin('0000');
        }
    }

    // HP keluarga

    public function test_unknown_device_sees_no_children_and_cannot_try_a_pin(): void
    {
        $this->get(route('child.pick'))->assertOk()->assertDontSee('Budi');

        $this->post(route('child.pin'), ['child_id' => $this->child->id, 'pin' => '4826'])->assertSessionHasErrors('pin');
        $this->assertGuest();
        $this->assertSame(0, $this->child->fresh()->pin_failures);
    }

    public function test_parent_login_marks_the_device_as_family_device(): void
    {
        $this->post(route('login.store'), ['email' => 'kris@example.test', 'password' => 'secret-pass'])->assertCookie('kq_family');
    }

    public function test_family_device_lists_children(): void
    {
        $this->withCookie('kq_family', '1')->get(route('child.pick'))->assertSee('Budi');
    }

    // Sesi parent

    public function test_parent_on_a_borrowed_device_times_out_after_ten_minutes(): void
    {
        $this->actingAs($this->parent)->withSession(['parent_last_seen' => now()->subMinutes(11)->timestamp])
            ->get(route('parent.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_own_device_checkbox_allows_two_hours(): void
    {
        $this->post(route('login.store'), ['email' => 'kris@example.test', 'password' => 'secret-pass', 'own_device' => '1'])
            ->assertSessionHas('parent_own_device', true)->assertSessionHas('parent_last_seen');

        $this->travel(30)->minutes();
        $this->get(route('parent.dashboard'))->assertOk();
    }

    public function test_chat_polling_does_not_keep_the_parent_session_alive(): void
    {
        $seen = now()->subMinutes(9)->timestamp;
        $this->actingAs($this->parent)->withSession(['parent_last_seen' => $seen])
            ->getJson(route('chat.poll', $this->child))->assertOk()->assertSessionHas('parent_last_seen', $seen);

        $this->travel(2)->minutes();
        $this->getJson(route('chat.poll', $this->child))->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_timeout_also_covers_routes_outside_the_parent_area(): void
    {
        $this->actingAs($this->parent)->withSession(['parent_last_seen' => now()->subHour()->timestamp])
            ->post(route('locale.update'), ['locale' => 'en'])->assertRedirect(route('login'));
        $this->assertSame('id', $this->parent->fresh()->locale);
    }

    public function test_child_pin_login_removes_parent_push_subscription_of_that_device_only(): void
    {
        $this->parent->updatePushSubscription('https://fcm.googleapis.com/shared', 'k', 't', 'aes128gcm');
        $this->parent->updatePushSubscription('https://fcm.googleapis.com/own-phone', 'k', 't', 'aes128gcm');

        $this->pin('4826', ['push_endpoint' => 'https://fcm.googleapis.com/shared']);

        $this->assertSame(['https://fcm.googleapis.com/own-phone'], PushSubscription::pluck('endpoint')->all());
    }

    // Auto lock

    public function test_five_wrong_pins_lock_the_account_for_good_and_notify_parent(): void
    {
        $this->parent->updatePushSubscription('https://fcm.googleapis.com/p', 'k', 't', 'aes128gcm');
        $this->lockChild();
        $this->travel(2)->days();

        $this->pin('4826')->assertSessionHasErrors('pin');
        $this->assertGuest();
        $this->assertNotNull($this->child->fresh()->pin_locked_at);
        Notification::assertSentToTimes($this->parent, PushMessage::class, 1);
    }

    public function test_correct_pin_resets_the_failure_count(): void
    {
        foreach (range(1, 4) as $_) {
            $this->pin('0000');
        }
        $this->pin('4826')->assertRedirect(route('child.missions'));

        $this->assertSame(0, $this->child->fresh()->pin_failures);
    }

    public function test_parent_unlocks_and_child_must_change_pin(): void
    {
        $this->lockChild();
        DB::table('sessions')->insert(['id' => 'old', 'user_id' => $this->child->id, 'payload' => '', 'last_activity' => 0]);

        $this->actingAs($this->parent)->get(route('parent.dashboard'))->assertSee(route('parent.children.unlock', $this->child));
        $this->post(route('parent.children.unlock', $this->child))->assertRedirect();
        $this->post(route('logout'));
        $this->assertSame(0, DB::table('sessions')->where('id', 'old')->count());

        $this->pin('4826')->assertRedirect(route('child.missions'));
        $this->get(route('child.missions'))->assertRedirect(route('child.profile'));
        $this->get(route('chat.index'))->assertRedirect(route('child.profile'));
        $this->get(route('child.profile'))->assertOk();

        $base = ['name' => 'Budi', 'avatar' => $this->child->avatar];
        $this->put(route('child.profile.update'), $base)->assertSessionHasErrors('pin');
        $this->put(route('child.profile.update'), [...$base, 'current_pin' => '4826', 'pin' => '4826'])->assertSessionHasErrors('pin');
        $this->put(route('child.profile.update'), [...$base, 'current_pin' => '4826', 'pin' => '1234'])->assertSessionHasErrors('pin');
        $this->put(route('child.profile.update'), [...$base, 'current_pin' => '4826', 'pin' => '7777'])->assertSessionHasErrors('pin');
        $this->put(route('child.profile.update'), [...$base, 'current_pin' => '4826', 'pin' => '9035'])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('9035', $this->child->fresh()->pin_hash));
        $this->get(route('child.missions'))->assertOk();
    }

    public function test_child_cannot_unlock_accounts(): void
    {
        $this->actingAs(User::factory()->child()->create())
            ->post(route('parent.children.unlock', $this->child))->assertForbidden();
    }

    // Nilai dobel

    private function exam(UploadedFile $photo, string $subject = 'IPA', int $score = 90)
    {
        return $this->actingAs($this->child)->post(route('child.exams.store'), ['subject' => $subject, 'score' => $score, 'photo' => $photo]);
    }

    private function photo(int $size = 100): UploadedFile
    {
        return UploadedFile::fake()->image('u.jpg', $size, $size);
    }

    public function test_same_photo_is_refused_after_approval_but_allowed_after_rejection(): void
    {
        $this->exam($this->photo());
        ExamResult::query()->update(['status' => ReviewStatus::Approved]);

        $this->exam($this->photo(), 'Matematika')->assertSessionHasErrors('photo');
        $this->assertSame(1, ExamResult::count());
        $this->assertCount(1, Storage::disk('local')->allFiles('photos'));

        ExamResult::query()->update(['status' => ReviewStatus::Rejected]);
        $this->exam($this->photo())->assertSessionHasNoErrors();
    }

    public function test_old_rows_without_hash_are_still_compared(): void
    {
        $this->exam($this->photo());
        ExamResult::query()->update(['status' => ReviewStatus::Approved, 'photo_hash' => null]);

        $this->exam($this->photo(), 'Matematika')->assertSessionHasErrors('photo');
    }

    public function test_at_most_five_exams_wait_for_review(): void
    {
        foreach (range(1, 5) as $i) {
            $this->exam($this->photo(100 + $i), "Pelajaran $i")->assertSessionHasNoErrors();
        }

        $this->exam($this->photo(200), 'Pelajaran 6')->assertSessionHasErrors('photo');
        $this->assertSame(5, ExamResult::count());
    }

    public function test_subject_punctuation_does_not_bypass_the_pending_check(): void
    {
        $this->exam($this->photo());
        $this->exam($this->photo(120), '  IPA. ')->assertSessionHasErrors('photo');

        $this->assertSame(1, ExamResult::count());
    }

    public function test_review_card_warns_and_history_links_the_photo(): void
    {
        $old = ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'subject' => 'IPA', 'claimed_score' => 90, 'final_score' => 90, 'status' => ReviewStatus::Approved, 'reviewed_at' => now()->subDays(3)]);
        ExamResult::factory()->withPhoto()->create(['child_id' => $this->child->id, 'subject' => 'IPA', 'claimed_score' => 90]);

        $this->actingAs($this->parent)->get(route('parent.dashboard'))->assertSee('Nilai yang sama sudah disetujui');
        $this->get(route('parent.history'))->assertSee(route('photos.exam', $old));
    }

    public function test_subject_capitals_do_not_bypass_the_pending_check(): void
    {
        $this->exam($this->photo());
        $this->exam($this->photo(120), 'ipa')->assertSessionHasErrors('photo');
    }

    public function test_warning_also_matches_the_score_claimed_before_a_correction(): void
    {
        ExamResult::factory()->create(['child_id' => $this->child->id, 'subject' => 'IPA', 'claimed_score' => 95, 'final_score' => 85, 'status' => ReviewStatus::Approved, 'reviewed_at' => now()->subDay()]);
        $again = ExamResult::factory()->create(['child_id' => $this->child->id, 'subject' => 'IPA', 'claimed_score' => 95]);

        $this->assertNotNull($again->sameMarkApproved());
    }

    // Lain-lain

    public function test_push_endpoint_must_be_a_known_push_service(): void
    {
        $this->actingAs($this->child);
        $send = fn (string $url) => $this->postJson(route('push.subscribe'), ['endpoint' => $url, 'keys' => ['p256dh' => 'a', 'auth' => 'b']]);

        foreach (['https://127.0.0.1/x', 'https://evil.example/x', 'https://fcm.googleapis.com.evil.example/x', 'https://evil.example/fcm.googleapis.com'] as $url) {
            $send($url)->assertUnprocessable();
        }
        foreach (['https://fcm.googleapis.com/fcm/send/a', 'https://updates.push.services.mozilla.com/wpush/v2/a', 'https://web.push.apple.com/a', 'https://wns2-sg2p.notify.windows.com/w/?token=a'] as $url) {
            $send($url)->assertNoContent();
        }
    }

    public function test_uploads_are_throttled(): void
    {
        foreach (range(1, 10) as $_) {
            $this->actingAs($this->child)->post(route('child.exams.store'), []);
        }

        $this->post(route('child.exams.store'), [])->assertTooManyRequests();
    }

    public function test_chat_polling_does_not_eat_the_send_limit(): void
    {
        $this->actingAs($this->child);
        foreach (range(1, 25) as $_) {
            $this->getJson(route('chat.poll', $this->parent))->assertOk();
        }

        $this->post(route('chat.store', $this->parent), ['body' => 'halo'])->assertRedirect();
    }

    public function test_parent_password_needs_ten_characters(): void
    {
        $this->artisan('kidsquest:make-parent', ['email' => 'b@example.test', 'name' => 'B'])
            ->expectsQuestion('Password', 'pendek')
            ->assertFailed();

        $this->assertNull(User::where('email', 'b@example.test')->first());
    }
}
