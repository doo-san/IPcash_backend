<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_up_reports_up_when_database_and_cache_answer(): void
    {
        $this->getJson('/up')->assertOk()->assertJson(['status' => 'up']);
    }

    public function test_up_reports_down_when_the_database_is_unreachable(): void
    {
        config([
            'app.debug' => false,
            'database.connections.sqlite.database' => '/nonexistent/dir/db.sqlite',
        ]);
        DB::purge('sqlite');

        $this->getJson('/up')->assertStatus(500)->assertJson(['status' => 'down']);
    }
}
