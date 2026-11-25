<?php
namespace Tests\Unit;
use Tests\TestCase;
class TestDatabaseGuardTest extends TestCase
{
    public function test_guard_refuses_non_test_database(): void
    {
        $this->expectException(\RuntimeException::class);
        config(['database.connections.mysql.database' => 'kidsquest']);
        $this->beforeRefreshingDatabase();
    }
}
