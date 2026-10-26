<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private function bearer(Account $account): array
    {
        auth()->forgetGuards();

        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_the_api_is_limited_to_120_requests_per_minute_per_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000301']);
        $other = Account::create(['phone_number' => '+221770000302']);
        $headers = $this->bearer($account);

        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/accounts/me', $headers)->assertOk();
        }
        $this->getJson('/api/accounts/me', $headers)->assertStatus(429);

        $this->getJson('/api/accounts/me', $this->bearer($other))->assertOk();
    }

    public function test_webhooks_keep_their_own_limit_and_skip_the_api_limiter(): void
    {
        $router = app('router');

        foreach (['wave.webhook' => '300,1', 'orange-money.webhook' => '120,1'] as $name => $limit) {
            $throttles = array_values(array_filter(
                $router->gatherRouteMiddleware(Route::getRoutes()->getByName($name)),
                fn (string $middleware) => str_contains($middleware, 'ThrottleRequests'),
            ));

            $this->assertSame(["Illuminate\\Routing\\Middleware\\ThrottleRequests:{$limit}"], $throttles, $name);
        }
    }

    public function test_support_chat_is_limited_to_10_messages_per_minute(): void
    {
        config(['services.anthropic.key' => null]);
        $account = Account::create(['phone_number' => '+221770000303']);
        $headers = $this->bearer($account);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/support/chat', ['message' => 'Bonjour'], $headers)->assertOk();
        }
        $this->postJson('/api/support/chat', ['message' => 'Bonjour'], $headers)->assertStatus(429);
    }

    public function test_history_limit_is_clamped_instead_of_crashing(): void
    {
        $account = Account::create(['phone_number' => '+221770000304']);
        $account->transactions()->create(['type' => 'cashIn', 'status' => 'completed', 'amount_xof' => 100, 'reference' => 'T-1']);
        $account->transactions()->create(['type' => 'cashIn', 'status' => 'completed', 'amount_xof' => 200, 'reference' => 'T-2']);

        foreach (['0', '-5'] as $limit) {
            $json = $this->getJson("/api/transactions?limit={$limit}", $this->bearer($account))->assertOk()->json();
            $this->assertCount(1, $json['items']);
            $this->assertNotNull($json['nextCursor']);
        }
    }
}
