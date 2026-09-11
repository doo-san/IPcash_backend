<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\MobileMoneyProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountProfileTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_responses_are_never_wrapped_in_a_data_key(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);

        $json = $this->getJson('/api/accounts/me', $this->authHeader($account))->assertOk()->json();

        $this->assertArrayHasKey('phoneNumber', $json);
        $this->assertArrayNotHasKey('data', $json);
    }

    public function test_account_balance_endpoint(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        $account->balance_xof = 42000;
        $account->save();

        $this->getJson('/api/accounts/me/balance', $this->authHeader($account))
            ->assertOk()
            ->assertJson(['balanceXof' => 42000, 'currency' => 'XOF']);
    }

    public function test_profile_show_and_update(): void
    {
        $account = Account::create(['phone_number' => '+221771234567', 'first_name' => 'Abdou', 'last_name' => 'Ba']);

        $this->getJson('/api/profile', $this->authHeader($account))
            ->assertOk()
            ->assertJson(['fullName' => 'Abdou Ba', 'preferredLocale' => 'fr']);

        $updated = $this->patchJson('/api/profile', [
            'fullName' => 'Moussa Fall',
            'email' => 'moussa@example.com',
            'notificationsEnabled' => false,
        ], $this->authHeader($account))->assertOk()->json();

        $this->assertSame('Moussa Fall', $updated['fullName']);
        $this->assertSame('moussa@example.com', $updated['email']);
        $this->assertFalse($updated['notificationsEnabled']);

        $account->refresh();
        $this->assertSame('Moussa', $account->first_name);
        $this->assertSame('Fall', $account->last_name);
    }

    public function test_sessions_lists_only_valid_ones_and_marks_the_current_device(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        $current = $account->refreshTokens()->create([
            'device_id' => 'device-A', 'token_hash' => bcrypt('a'), 'expires_at' => now()->addDays(30),
        ]);
        $other = $account->refreshTokens()->create([
            'device_id' => 'device-B', 'token_hash' => bcrypt('b'), 'expires_at' => now()->addDays(30),
        ]);
        // Ignorés : révoqué et expiré.
        $account->refreshTokens()->create([
            'device_id' => 'device-C', 'token_hash' => bcrypt('c'), 'expires_at' => now()->addDays(30), 'revoked_at' => now(),
        ]);
        $account->refreshTokens()->create([
            'device_id' => 'device-D', 'token_hash' => bcrypt('d'), 'expires_at' => now()->subDay(),
        ]);

        $json = $this->getJson('/api/profile/sessions?deviceId=device-A', $this->authHeader($account))
            ->assertOk()->json();

        $this->assertCount(2, $json);
        $byId = collect($json)->keyBy('id');
        $this->assertTrue($byId[$current->id]['isCurrent']);
        $this->assertFalse($byId[$other->id]['isCurrent']);
        $this->assertSame('device-B', $byId[$other->id]['deviceLabel']);
    }

    public function test_a_client_can_revoke_another_session_but_not_another_accounts(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        $mine = $account->refreshTokens()->create([
            'device_id' => 'device-B', 'token_hash' => bcrypt('b'), 'expires_at' => now()->addDays(30),
        ]);

        $other = Account::create(['phone_number' => '+221770000000']);
        $theirs = $other->refreshTokens()->create([
            'device_id' => 'device-X', 'token_hash' => bcrypt('x'), 'expires_at' => now()->addDays(30),
        ]);

        $this->deleteJson("/api/profile/sessions/{$theirs->id}", [], $this->authHeader($account))
            ->assertStatus(404);
        $this->assertNull($theirs->fresh()->revoked_at);

        $this->deleteJson("/api/profile/sessions/{$mine->id}", [], $this->authHeader($account))
            ->assertStatus(204);
        $this->assertNotNull($mine->fresh()->revoked_at);
    }

    public function test_mobile_money_providers_only_returns_active(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        MobileMoneyProvider::create([
            'name' => 'Orange Money', 'min_amount_xof' => 500, 'max_amount_xof' => 2000000,
            'flow' => 'ussd', 'country_dial_code' => '+221', 'currency_code' => 'XOF',
        ]);
        MobileMoneyProvider::create([
            'name' => 'Défunt Money', 'min_amount_xof' => 500, 'max_amount_xof' => 2000000,
            'flow' => 'ussd', 'country_dial_code' => '+221', 'currency_code' => 'XOF', 'is_active' => false,
        ]);

        $json = $this->getJson('/api/providers/mobile-money', $this->authHeader($account))
            ->assertOk()
            ->json();

        $this->assertCount(1, $json);
        $this->assertSame('Orange Money', $json[0]['name']);
        $this->assertArrayNotHasKey('apiKey', $json[0]);
    }

    public function test_mobile_money_provider_logo_url_is_absolute(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        $provider = MobileMoneyProvider::create([
            'name' => 'Orange Money', 'min_amount_xof' => 500, 'max_amount_xof' => 2000000,
            'flow' => 'ussd', 'country_dial_code' => '+221', 'currency_code' => 'XOF',
            'logo_url' => 'logos/mobile-money/orange.png',
        ]);

        $json = $this->getJson('/api/providers/mobile-money', $this->authHeader($account))->assertOk()->json();

        $this->assertStringContainsString('/storage/logos/mobile-money/orange.png', $json[0]['logoUrl']);
        $this->assertNull((new MobileMoneyProvider(['logo_url' => null]))->logoUrl());
    }

    public function test_transaction_list_is_paginated_by_cursor_and_scoped_to_own_account(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        $other = Account::create(['phone_number' => '+221779998877']);

        for ($i = 0; $i < 5; $i++) {
            $account->transactions()->create([
                'type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 1000 + $i,
                'reference' => "TX-{$i}",
            ]);
        }
        $other->transactions()->create(['type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 999, 'reference' => 'TX-OTHER']);

        $page1 = $this->getJson('/api/transactions?limit=2', $this->authHeader($account))->assertOk()->json();
        $this->assertCount(2, $page1['items']);
        $this->assertNotNull($page1['nextCursor']);

        $page2 = $this->getJson("/api/transactions?limit=2&cursor={$page1['nextCursor']}", $this->authHeader($account))
            ->assertOk()->json();
        $this->assertCount(2, $page2['items']);

        // Aucun chevauchement entre les deux pages.
        $ids1 = array_column($page1['items'], 'id');
        $ids2 = array_column($page2['items'], 'id');
        $this->assertEmpty(array_intersect($ids1, $ids2));

        // Jamais la transaction d'un autre compte.
        $allRefs = array_merge(
            array_column($page1['items'], 'reference'),
            array_column($page2['items'], 'reference'),
        );
        $this->assertNotContains('TX-OTHER', $allRefs);
    }

    public function test_transaction_show_returns_404_for_other_accounts_transaction(): void
    {
        $account = Account::create(['phone_number' => '+221771234567']);
        $other = Account::create(['phone_number' => '+221779998877']);
        $tx = $other->transactions()->create(['type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 500, 'reference' => 'TX-X']);

        $this->getJson("/api/transactions/{$tx->id}", $this->authHeader($account))->assertStatus(404);
    }
}
