<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoadTestTransfersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('loadtest:transfers')
            ->expectsOutputToContain('Refusé en production.')
            ->assertExitCode(1);
        $this->assertSame(0, Account::count());
    }

    public function test_it_fails_when_no_transfer_went_through_and_cleans_up(): void
    {
        Http::fake(['*' => Http::response(['code' => 'MAINTENANCE'], 503)]);

        $this->artisan('loadtest:transfers', ['--accounts' => 3, '--requests' => 5, '--concurrency' => 5])
            ->expectsOutputToContain('ÉCHEC')
            ->assertExitCode(1);
        $this->assertSame(0, Account::count());
    }
}
