<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionTest extends TestCase
{
    public function test_version_matches_file(): void
    {
        $this->assertSame(trim(file_get_contents(base_path('VERSION'))), config('app.version'));
    }
}
