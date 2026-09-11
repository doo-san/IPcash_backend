<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Card;
use App\Models\FeeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CardFlowTest extends TestCase
{
    use RefreshDatabase;

    private function account(int $balance = 0): Account
    {
        $account = Account::create(['phone_number' => '+2217712'.random_int(10000, 99999)]);
        $account->pin_hash = bcrypt('123456');
        $account->balance_xof = $balance;
        $account->save();

        return $account;
    }

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    private function idempotentHeader(Account $account): array
    {
        return array_merge($this->authHeader($account), ['Idempotency-Key' => Str::uuid()->toString()]);
    }

    public function test_freeze_and_unfreeze_card(): void
    {
        $account = $this->account();
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->postJson("/api/cards/{$card->id}/freeze", [], $this->authHeader($account))
            ->assertOk()->assertJson(['status' => 'frozen']);
        $this->assertSame('frozen', $card->fresh()->status->value);

        $this->postJson("/api/cards/{$card->id}/unfreeze", [], $this->authHeader($account))
            ->assertOk()->assertJson(['status' => 'active']);
    }

    public function test_client_cannot_freeze_or_unfreeze_a_card_blocked_by_the_admin(): void
    {
        $account = $this->account();
        $card = $account->cards()->create([
            'last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'status' => 'blocked',
        ]);

        $this->postJson("/api/cards/{$card->id}/unfreeze", [], $this->authHeader($account))
            ->assertStatus(403)->assertJson(['code' => 'CARD_BLOCKED']);

        $this->postJson("/api/cards/{$card->id}/freeze", [], $this->authHeader($account))
            ->assertStatus(403)->assertJson(['code' => 'CARD_BLOCKED']);

        $this->assertSame('blocked', $card->fresh()->status->value);
    }

    public function test_reveal_requires_correct_pin_and_returns_short_lived_token(): void
    {
        $account = $this->account();
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->postJson("/api/cards/{$card->id}/reveal", ['pin' => '000000'], $this->authHeader($account))
            ->assertStatus(401);

        $response = $this->postJson("/api/cards/{$card->id}/reveal", ['pin' => '123456'], $this->authHeader($account))
            ->assertOk()->json();

        $this->assertArrayHasKey('revealToken', $response);
        $this->assertSame(30, $response['expiresInSeconds']);
        $this->assertSame(1, $card->revealTokens()->count());
    }

    public function test_card_actions_scoped_to_own_account(): void
    {
        $account = $this->account();
        $other = $this->account();
        $card = $other->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->postJson("/api/cards/{$card->id}/freeze", [], $this->authHeader($account))->assertStatus(404);
    }

    public function test_generate_ephemeral_card_produces_luhn_invalid_number(): void
    {
        $account = $this->account();
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $response = $this->postJson("/api/cards/{$card->id}/ephemeral-card", ['pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(202)->json();

        $this->assertSame(16, strlen($response['number']));
        $this->assertSame(3, strlen($response['cvv']));
        $this->assertSame(0, $response['balanceXof']);
        $this->assertFalse($this->isLuhnValid($response['number']));
    }

    public function test_only_one_ephemeral_card_active_at_a_time(): void
    {
        $account = $this->account();
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $first = $this->postJson("/api/cards/{$card->id}/ephemeral-card", ['pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(202)->json();

        // Entre deux appels `postJson()` simulant deux requêtes distinctes,
        // le guard Sanctum garde en cache l'utilisateur (et ses relations
        // déjà résolues) du premier appel — artefact du harnais de test
        // uniquement, voir AuthFlowTest::test_logout_revokes_access_and_refresh_tokens.
        auth()->forgetGuards();

        $second = $this->postJson("/api/cards/{$card->id}/ephemeral-card", ['pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(202)->json();

        $this->assertSame($first['number'], $second['number']);
    }

    public function test_get_ephemeral_card_returns_null_when_none_active(): void
    {
        $account = $this->account();

        $this->getJson('/api/cards/ephemeral-card', $this->authHeader($account))
            ->assertOk()
            ->assertContent('null');
    }

    public function test_recharge_and_transfer_out_move_balance_between_main_and_ephemeral(): void
    {
        $account = $this->account(50000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);
        $this->postJson("/api/cards/{$card->id}/ephemeral-card", ['pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(202);
        auth()->forgetGuards();

        $this->postJson('/api/cards/ephemeral-card/recharge', ['amountXof' => 20000], $this->idempotentHeader($account))
            ->assertStatus(202)->assertJson(['type' => 'cardTopUp', 'amountXof' => -20000]);
        auth()->forgetGuards();

        $this->assertSame(30000, $account->fresh()->balance_xof);
        $this->assertSame(20000, $account->fresh()->ephemeralCard->balance_xof);

        $this->postJson('/api/cards/ephemeral-card/transfer-out', ['amountXof' => 5000], $this->idempotentHeader($account))
            ->assertStatus(202)->assertJson(['type' => 'cardWithdrawal', 'amountXof' => 5000]);

        $this->assertSame(35000, $account->fresh()->balance_xof);
        $this->assertSame(15000, $account->fresh()->ephemeralCard->balance_xof);
    }

    public function test_destroy_ephemeral_card_refunds_remaining_balance(): void
    {
        $account = $this->account(50000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);
        $this->postJson("/api/cards/{$card->id}/ephemeral-card", ['pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(202);
        auth()->forgetGuards();
        $this->postJson('/api/cards/ephemeral-card/recharge', ['amountXof' => 10000], $this->idempotentHeader($account))
            ->assertStatus(202);
        auth()->forgetGuards();

        $this->deleteJson('/api/cards/ephemeral-card', [], $this->authHeader($account))->assertStatus(204);

        $this->assertSame(50000, $account->fresh()->balance_xof);
        $this->assertNull($account->fresh()->ephemeralCard);
    }

    public function test_deleting_a_card_sweeps_its_balance_back_to_the_account(): void
    {
        $account = $this->account(10000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 7500]);

        $this->deleteJson("/api/cards/{$card->id}", [], $this->idempotentHeader($account))->assertStatus(204);

        $this->assertSame(17500, $account->fresh()->balance_xof);
        $this->assertNull(Card::find($card->id));

        $tx = $account->transactions()->latest()->first();
        $this->assertSame('cardWithdrawal', $tx->type->value);
        $this->assertSame(7500, $tx->amount_xof);
    }

    public function test_deleting_a_card_with_no_balance_creates_no_ledger_entry(): void
    {
        $account = $this->account(10000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->deleteJson("/api/cards/{$card->id}", [], $this->idempotentHeader($account))->assertStatus(204);

        $this->assertSame(10000, $account->fresh()->balance_xof);
        $this->assertSame(0, $account->transactions()->count());
    }

    public function test_deleting_a_card_twice_with_the_same_idempotency_key_is_safe(): void
    {
        $account = $this->account(10000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 5000]);
        $headers = $this->idempotentHeader($account);

        $this->deleteJson("/api/cards/{$card->id}", [], $headers)->assertStatus(204);
        auth()->forgetGuards();
        $this->deleteJson("/api/cards/{$card->id}", [], $headers)->assertStatus(204);

        $this->assertSame(15000, $account->fresh()->balance_xof);
        $this->assertSame(1, $account->transactions()->count());
    }

    public function test_deleting_another_accounts_card_is_not_found(): void
    {
        $account = $this->account();
        $other = $this->account();
        $card = $other->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->deleteJson("/api/cards/{$card->id}", [], $this->idempotentHeader($account))->assertStatus(404);
    }

    public function test_card_index_includes_balance_xof(): void
    {
        $account = $this->account();
        $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 1234]);

        $json = $this->getJson('/api/cards', $this->authHeader($account))->assertOk()->json();

        $this->assertSame(1234, $json[0]['balanceXof']);
    }

    public function test_recharge_and_transfer_out_move_balance_between_main_and_persistent_card(): void
    {
        $account = $this->account(50000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $tx = $this->postJson("/api/cards/{$card->id}/recharge", ['amountXof' => 20000], $this->idempotentHeader($account))
            ->assertStatus(202)->json();
        $this->assertSame('cardTopUp', $tx['type']);
        $this->assertSame(20000, $tx['amountXof']);
        $this->assertSame(30000, $tx['mainBalanceAfterXof']);
        $this->assertSame(20000, $tx['cardBalanceAfterXof']);
        $this->assertSame('4242', $tx['cardLast4']);

        $this->assertSame(30000, $account->fresh()->balance_xof);
        $this->assertSame(20000, $card->fresh()->balance_xof);

        $out = $this->postJson("/api/cards/{$card->id}/transfer-out", ['amountXof' => 5000], $this->idempotentHeader($account))
            ->assertStatus(202)->json();
        $this->assertSame('cardWithdrawal', $out['type']);
        $this->assertSame(-5000, $out['amountXof']);

        $this->assertSame(35000, $account->fresh()->balance_xof);
        $this->assertSame(15000, $card->fresh()->balance_xof);
    }

    public function test_recharge_adds_the_admin_configured_fee_to_the_main_balance_debit_only(): void
    {
        FeeRule::create(['scope' => 'cardTopUp', 'type' => 'fixed', 'value' => 100]);
        $account = $this->account(50000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $tx = $this->postJson("/api/cards/{$card->id}/recharge", ['amountXof' => 20000], $this->idempotentHeader($account))
            ->assertStatus(202)->json();
        $this->assertSame(20100, $tx['amountXof']);

        $this->assertSame(29900, $account->fresh()->balance_xof);
        $this->assertSame(20000, $card->fresh()->balance_xof);
    }

    public function test_recharge_exceeding_main_balance_is_rejected(): void
    {
        $account = $this->account(1000);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->postJson("/api/cards/{$card->id}/recharge", ['amountXof' => 5000], $this->idempotentHeader($account))
            ->assertStatus(402);
    }

    public function test_card_transactions_are_scoped_to_that_card(): void
    {
        $account = $this->account(50000);
        $cardA = $account->cards()->create(['last4' => '1111', 'expiry_month' => 9, 'expiry_year' => 2028]);
        $cardB = $account->cards()->create(['last4' => '2222', 'expiry_month' => 9, 'expiry_year' => 2028]);

        $this->postJson("/api/cards/{$cardA->id}/recharge", ['amountXof' => 1000], $this->idempotentHeader($account))->assertStatus(202);
        auth()->forgetGuards();
        $this->postJson("/api/cards/{$cardB->id}/recharge", ['amountXof' => 2000], $this->idempotentHeader($account))->assertStatus(202);

        $historyA = $this->getJson("/api/cards/{$cardA->id}/transactions", $this->authHeader($account))->assertOk()->json();
        $this->assertCount(1, $historyA);
        $this->assertSame(1000, $historyA[0]['amountXof']);
    }

    private function isLuhnValid(string $number): bool
    {
        $sum = 0;
        $double = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $d = (int) $number[$i];
            if ($double) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }
}
