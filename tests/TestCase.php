<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Pengaman: dev dan produksi berbagi folder; jangan pernah migrate:fresh ke database selain test. */
    protected function beforeRefreshingDatabase()
    {
        $db = config('database.connections.'.config('database.default').'.database');

        if ($db !== 'kidsquest_test') {
            throw new \RuntimeException("Menolak menjalankan test di database '{$db}'. Hapus cache config: php artisan config:clear");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
