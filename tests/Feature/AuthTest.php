<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function wrongPins(User $child, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '0000']);
        }
    }

    public function test_parent_logs_in_with_email_password(): void
    {
        User::factory()->parent()->create(['email' => 'kris@example.test', 'password' => 'secret-pass']);

        $this->post(route('login.store'), ['email' => 'kris@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_parent_login_fails_with_wrong_password(): void
    {
        User::factory()->parent()->create(['email' => 'kris@example.test', 'password' => 'secret-pass']);

        $this->post(route('login.store'), ['email' => 'kris@example.test', 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_child_logs_in_with_correct_pin(): void
    {
        $child = User::factory()->child('4321')->create();

        $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '4321'])
            ->assertRedirect(route('child.missions'));
        $this->assertAuthenticatedAs($child);
    }

    public function test_wrong_pin_rejected(): void
    {
        $child = User::factory()->child('4321')->create();

        $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '1111'])
            ->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_sixth_attempt_is_throttled_even_with_correct_pin(): void
    {
        $child = User::factory()->child('4321')->create();
        $this->wrongPins($child, 5);

        $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '4321'])
            ->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_throttle_is_per_child(): void
    {
        $a = User::factory()->child('1111')->create();
        $b = User::factory()->child('2222')->create();
        $this->wrongPins($a, 5);

        $this->post(route('child.pin'), ['child_id' => $b->id, 'pin' => '2222'])
            ->assertRedirect(route('child.missions'));
        $this->assertAuthenticatedAs($b);
    }

    public function test_pin_must_be_exactly_4_digits(): void
    {
        $child = User::factory()->child('1234')->create();

        foreach (['12a4', '123', '12345', ''] as $pin) {
            $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => $pin])
                ->assertSessionHasErrors('pin');
        }
        $this->assertGuest();
    }

    public function test_child_cannot_open_parent_routes_and_vice_versa(): void
    {
        $this->actingAs(User::factory()->child()->create())
            ->get(route('parent.dashboard'))->assertForbidden();

        $this->actingAs(User::factory()->parent()->create())
            ->get(route('child.missions'))->assertForbidden();
    }

    public function test_guests_are_redirected_from_protected_routes(): void
    {
        $this->get(route('parent.dashboard'))->assertRedirect(route('login'));
        $this->get(route('child.missions'))->assertRedirect(route('child.pick'));
    }

    public function test_root_redirects_logged_in_users_by_role(): void
    {
        $this->actingAs(User::factory()->parent()->create())->get('/')->assertRedirect(route('parent.dashboard'));
        $this->actingAs(User::factory()->child()->create())->get('/')->assertRedirect(route('child.missions'));
    }

    public function test_picker_lists_children_only(): void
    {
        User::factory()->child()->create(['name' => 'Budi']);
        User::factory()->parent()->create(['name' => 'Ortu']);
        User::factory()->child()->create(['name' => 'Hapus'])->delete();

        $this->get('/')->assertOk()->assertSee('Budi')->assertDontSee('Ortu')->assertDontSee('Hapus');
    }

    public function test_soft_deleted_child_cannot_login(): void
    {
        $child = User::factory()->child('4321')->create();
        $child->delete();

        $this->post(route('child.pin'), ['child_id' => $child->id, 'pin' => '4321'])
            ->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_parent_cannot_use_the_child_pin_endpoint(): void
    {
        $parent = User::factory()->parent()->create();

        $this->post(route('child.pin'), ['child_id' => $parent->id, 'pin' => '1234'])
            ->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->child()->create())
            ->post(route('logout'))->assertRedirect(route('child.pick'));
        $this->assertGuest();
    }

    public function test_no_register_route_exists(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertStatus(404);
    }

    public function test_command_creates_parent(): void
    {
        $this->artisan('kidsquest:make-parent', ['email' => 'kris@example.test', 'name' => 'Kris'])
            ->expectsQuestion('Password', 'secret-pass')
            ->assertSuccessful();

        $parent = User::where('email', 'kris@example.test')->firstOrFail();
        $this->assertTrue($parent->isParent());
    }
}
