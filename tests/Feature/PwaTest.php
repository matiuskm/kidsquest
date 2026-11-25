<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_manifest_is_installable(): void
    {
        $m = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $m['display']);
        $this->assertSame('/', $m['start_url']);
        $this->assertNotEmpty($m['name']);
        $sizes = collect($m['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        foreach ($m['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_handles_push_and_clicks(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        foreach (["addEventListener('push'", "addEventListener('notificationclick'", "addEventListener('fetch'"] as $needle) {
            $this->assertStringContainsString($needle, $sw);
        }
    }

    public function test_pages_link_the_manifest_and_vapid_key(): void
    {
        config(['webpush.vapid.public_key' => 'TESTKEY123']);

        $this->get('/')->assertOk()
            ->assertSee('rel="manifest"', false)->assertSee('/manifest.webmanifest', false)
            ->assertSee('TESTKEY123', false);
    }

    public function test_sound_files_exist(): void
    {
        foreach (['points', 'redeem', 'reject', 'submit'] as $name) {
            $this->assertFileExists(public_path("sounds/$name.wav"));
        }
    }
}
