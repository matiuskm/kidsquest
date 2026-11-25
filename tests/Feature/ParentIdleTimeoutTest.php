<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentIdleTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_idle_for_over_two_hours_is_logged_out(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->withSession(['parent_last_seen' => now()->subHours(3)->timestamp])
            ->get(route('parent.dashboard'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_parent_stays_logged_in_and_activity_is_refreshed(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->withSession(['parent_last_seen' => now()->subMinutes(30)->timestamp])
            ->get(route('parent.dashboard'))->assertOk();

        $this->assertGreaterThan(now()->subMinute()->timestamp, session('parent_last_seen'));
    }

    public function test_child_sessions_are_not_affected(): void
    {
        $child = User::factory()->child()->create();

        $this->actingAs($child)->withSession(['parent_last_seen' => now()->subDays(5)->timestamp])
            ->get(route('child.missions'))->assertOk();
    }
}
