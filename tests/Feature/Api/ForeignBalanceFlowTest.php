<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Sous-comptes devise IPchange (ForeignBalanceController) — aucun test
// n'existait pour ce contrôleur avant l'ajout du verrou pessimiste
// (LocksRowsForUpdate) sur ses trois méthodes qui débitent un solde.
class ForeignBalanceFlowTest extends TestCase
{
    use RefreshDatabase;

    private function account(int $balanceXof = 0): Account
    {
        $account = Account::create(['phone_number' => '+2217713'.random_int(10000, 99999)]);
        $account->pin_hash = bcrypt('123456');
        $account->balance_xof = $balanceXof;
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

    // Déjà présent après les migrations (seed_exchange_rates_catalog) — ce
    // helper se contente de vérifier qu'il est bien là plutôt que de
    // risquer un doublon (`currency_code` est la clé primaire).
    private function eurRate(): ExchangeRate
    {
        return ExchangeRate::findOrFail('EUR');
    }

    public function test_convert_from_xof_debits_the_main_balance_and_credits_the_foreign_balance(): void
    {
        $this->eurRate();
        $account = $this->account(100000);

        $tx = $this->postJson('/api/foreign-balances/convert-from-xof', [
            'currencyCode' => 'eur', 'amountXof' => 65596,
        ], $this->idempotentHeader($account))->assertStatus(202)->json();

        $this->assertSame('foreignExchangeOut', $tx['type']);
        $this->assertSame(-65596, $tx['amountXof']);
        $this->assertSame(34404, $account->fresh()->balance_xof);
        $this->assertSame(10000, $account->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_convert_from_xof_exceeding_main_balance_is_rejected(): void
    {
        $this->eurRate();
        $account = $this->account(1000);

        $this->postJson('/api/foreign-balances/convert-from-xof', [
            'currencyCode' => 'eur', 'amountXof' => 65596,
        ], $this->idempotentHeader($account))->assertStatus(402);

        $this->assertSame(1000, $account->fresh()->balance_xof);
        $this->assertSame(0, $account->fresh()->foreignBalances()->count());
    }

    public function test_convert_to_xof_debits_the_foreign_balance_and_credits_the_main_balance(): void
    {
        $this->eurRate();
        $account = $this->account(0);
        $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 10000]);

        $tx = $this->postJson('/api/foreign-balances/convert-to-xof', [
            'currencyCode' => 'eur', 'amountMinorUnits' => 10000,
        ], $this->idempotentHeader($account))->assertStatus(202)->json();

        $this->assertSame('foreignExchangeIn', $tx['type']);
        $this->assertSame(65596, $tx['amountXof']);
        $this->assertSame(65596, $account->fresh()->balance_xof);
        $this->assertSame(0, $account->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_convert_to_xof_exceeding_the_foreign_balance_is_rejected(): void
    {
        $this->eurRate();
        $account = $this->account(0);
        $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 1000]);

        $this->postJson('/api/foreign-balances/convert-to-xof', [
            'currencyCode' => 'eur', 'amountMinorUnits' => 10000,
        ], $this->idempotentHeader($account))->assertStatus(402);

        $this->assertSame(0, $account->fresh()->balance_xof);
        $this->assertSame(1000, $account->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_send_moves_foreign_balance_between_two_ipcash_accounts(): void
    {
        $this->eurRate();
        $sender = $this->account(0);
        $sender->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 10000]);
        $recipient = Account::create(['phone_number' => '+221772223333']);

        $tx = $this->postJson('/api/foreign-balances/send', [
            'currencyCode' => 'eur', 'amountMinorUnits' => 4000,
            'recipientPhoneNumber' => '+221772223333', 'pin' => '123456',
        ], $this->idempotentHeader($sender))->assertStatus(202)->json();

        $this->assertSame('foreignTransferOut', $tx['type']);
        $this->assertSame(6000, $sender->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
        $this->assertSame(4000, $recipient->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_send_exceeding_the_foreign_balance_is_rejected(): void
    {
        $this->eurRate();
        $sender = $this->account(0);
        $sender->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 1000]);
        Account::create(['phone_number' => '+221772223333']);

        $this->postJson('/api/foreign-balances/send', [
            'currencyCode' => 'eur', 'amountMinorUnits' => 4000,
            'recipientPhoneNumber' => '+221772223333', 'pin' => '123456',
        ], $this->idempotentHeader($sender))->assertStatus(402);

        $this->assertSame(1000, $sender->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_send_rejects_the_wrong_pin(): void
    {
        $this->eurRate();
        $sender = $this->account(0);
        $sender->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 10000]);
        Account::create(['phone_number' => '+221772223333']);

        $this->postJson('/api/foreign-balances/send', [
            'currencyCode' => 'eur', 'amountMinorUnits' => 4000,
            'recipientPhoneNumber' => '+221772223333', 'pin' => '000000',
        ], $this->idempotentHeader($sender))->assertStatus(401);

        $this->assertSame(10000, $sender->fresh()->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }
}
