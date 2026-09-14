<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_route_serves_the_public_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('IPCash');
    }
}
