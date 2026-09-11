<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\ExchangeRate;
use App\Models\FeeRule;
use App\Models\MobileMoneyProvider;
use App\Models\PromoCode;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashioFlowTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $phone, int $balance = 0): Account
    {
        $account = Account::create(['phone_number' => $phone]);
        $account->pin_hash = bcrypt('123456');
        $account->balance_xof = $balance;
        $account->save();

        return $account;
    }

    private function authHeader(Account $account): array
    {
        return array_merge(
            ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken],
            ['Idempotency-Key' => Str::uuid()->toString()],
        );
    }

    public function test_cashin_credits_balance(): void
    {
        $account = $this->account('+221771111111', 0);

        $this->postJson('/api/cashin', [
            'providerId' => 'orange-money', 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000,
        ], $this->authHeader($account))->assertStatus(202)->assertJson(['type' => 'cashIn', 'amountXof' => 5000]);

        $this->assertSame(5000, $account->fresh()->balance_xof);
    }

    public function test_cashin_deducts_the_admin_configured_fee_from_the_credited_amount(): void
    {
        FeeRule::create(['scope' => 'cashIn', 'type' => 'percent', 'value' => 100]); // 1 %
        $account = $this->account('+221771111111', 0);

        $response = $this->postJson('/api/cashin', [
            'providerId' => 'orange-money', 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000,
        ], $this->authHeader($account))->assertStatus(202)->json();

        $this->assertSame(4950, $response['amountXof']);
        $this->assertSame(4950, $account->fresh()->balance_xof);
    }

    private function orangeMoneyProvider(): MobileMoneyProvider
    {
        return MobileMoneyProvider::create([
            'name' => 'Orange Money',
            'min_amount_xof' => 100,
            'max_amount_xof' => 1000000,
            'flow' => 'redirect',
            'country_dial_code' => '+221',
            'currency_code' => 'XOF',
            'api_base_url' => 'https://api.orange-sonatel.com',
            'api_key' => 'test-client-id',
            'api_secret' => 'test-client-secret',
            'merchant_code' => '501971',
            'is_live' => true,
        ]);
    }

    public function test_cashin_via_orange_money_creates_a_pending_transaction_and_returns_a_payment_url(): void
    {
        Http::fake([
            'api.orange-sonatel.com/oauth/v1/token' => Http::response(['access_token' => 'tok', 'expires_in' => 299]),
            'api.orange-sonatel.com/v1/onlinePayment/prepare' => Http::response(['paymentUrl' => 'https://om-pay.com/checkout/123']),
        ]);
        $provider = $this->orangeMoneyProvider();
        $account = $this->account('+221771111111', 0);

        $response = $this->postJson('/api/cashin', [
            'providerId' => $provider->id, 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000,
        ], $this->authHeader($account))->assertStatus(202)->json();

        $this->assertSame('pending', $response['status']);
        $this->assertSame('https://om-pay.com/checkout/123', $response['paymentUrl']);
        // Pas crédité tant que le webhook n'a pas confirmé (règle 3).
        $this->assertSame(0, $account->fresh()->balance_xof);
    }

    public function test_cashin_via_orange_money_records_a_failed_transaction_when_the_provider_errors(): void
    {
        Http::fake([
            'api.orange-sonatel.com/oauth/v1/token' => Http::response(['access_token' => 'tok', 'expires_in' => 299]),
            'api.orange-sonatel.com/v1/onlinePayment/prepare' => Http::response(['detail' => 'Customer msisdn is invalid'], 400),
        ]);
        $provider = $this->orangeMoneyProvider();
        $account = $this->account('+221771111111', 0);

        $response = $this->postJson('/api/cashin', [
            'providerId' => $provider->id, 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000,
        ], $this->authHeader($account))->assertStatus(202)->json();

        $this->assertSame('failed', $response['status']);
        $this->assertStringContainsString('Customer msisdn is invalid', $response['failureReason']);
        $this->assertSame(0, $account->fresh()->balance_xof);
    }

    public function test_orange_money_webhook_completes_a_pending_cashin_and_credits_the_balance(): void
    {
        $account = $this->account('+221771111111', 0);
        $transaction = $account->transactions()->create([
            'type' => 'cashIn', 'status' => 'pending', 'amount_xof' => 5000,
            'reference' => 'CASHIN-TESTREF01',
        ]);

        $this->postJson('/api/webhooks/orange-money', [
            'reference' => 'CASHIN-TESTREF01', 'status' => 'SUCCESS',
        ])->assertOk();

        $this->assertSame('completed', $transaction->fresh()->status->value);
        $this->assertSame(5000, $account->fresh()->balance_xof);
    }

    public function test_orange_money_webhook_fails_a_pending_cashin_without_crediting(): void
    {
        $account = $this->account('+221771111111', 0);
        $transaction = $account->transactions()->create([
            'type' => 'cashIn', 'status' => 'pending', 'amount_xof' => 5000,
            'reference' => 'CASHIN-TESTREF02',
        ]);

        $this->postJson('/api/webhooks/orange-money', [
            'reference' => 'CASHIN-TESTREF02', 'status' => 'FAILED',
        ])->assertOk();

        $this->assertSame('failed', $transaction->fresh()->status->value);
        $this->assertSame(0, $account->fresh()->balance_xof);
    }

    public function test_orange_money_webhook_ignores_unknown_reference(): void
    {
        $this->postJson('/api/webhooks/orange-money', [
            'reference' => 'CASHIN-DOESNOTEXIST', 'status' => 'SUCCESS',
        ])->assertOk();

        $this->assertSame(0, Transaction::count());
    }

    public function test_cashout_adds_the_admin_configured_fee_to_the_debited_amount(): void
    {
        FeeRule::create(['scope' => 'cashOut', 'type' => 'percent', 'value' => 100]); // 1 %
        $account = $this->account('+221771111111', 10000);

        $response = $this->postJson('/api/cashout', [
            'providerId' => 'orange-money', 'amountXof' => 4000, 'pin' => '123456',
        ], $this->authHeader($account))->assertStatus(202)->json();

        $this->assertSame(-4040, $response['amountXof']);
        $this->assertSame(5960, $account->fresh()->balance_xof);
    }

    public function test_a_valid_promo_code_reduces_the_fee_and_is_redeemed(): void
    {
        FeeRule::create(['scope' => 'cashIn', 'type' => 'fixed', 'value' => 200]);
        $promo = PromoCode::create(['code' => 'BIENVENUE', 'discount_type' => 'fixed', 'discount_value' => 200]);
        $account = $this->account('+221771111111', 0);

        $response = $this->postJson('/api/cashin', [
            'providerId' => 'orange-money', 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000, 'promoCode' => 'bienvenue',
        ], $this->authHeader($account))->assertStatus(202)->json();

        $this->assertSame(5000, $response['amountXof']); // frais de 200 entièrement annulés par le code
        $this->assertSame(1, $promo->fresh()->redemptions_count);
    }

    public function test_an_invalid_promo_code_is_rejected_explicitly(): void
    {
        $account = $this->account('+221771111111', 0);

        $this->postJson('/api/cashin', [
            'providerId' => 'orange-money', 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000, 'promoCode' => 'INCONNU',
        ], $this->authHeader($account))
            ->assertStatus(400)
            ->assertJson(['code' => 'PROMO_CODE_INVALID']);

        $this->assertSame(0, $account->fresh()->balance_xof);
    }

    public function test_an_expired_promo_code_is_rejected(): void
    {
        PromoCode::create([
            'code' => 'PERIME', 'discount_type' => 'fixed', 'discount_value' => 200,
            'valid_until' => now()->subDay(),
        ]);
        $account = $this->account('+221771111111', 0);

        $this->postJson('/api/cashin', [
            'providerId' => 'orange-money', 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 5000, 'promoCode' => 'PERIME',
        ], $this->authHeader($account))
            ->assertStatus(400)
            ->assertJson(['code' => 'PROMO_CODE_INVALID']);
    }

    public function test_cashin_card_never_fakes_success_but_records_the_attempt(): void
    {
        $account = $this->account('+221771111111');

        $this->postJson('/api/cashin/card', ['amountXof' => 5000], $this->authHeader($account))
            ->assertStatus(400)
            ->assertJson(['code' => 'INTEGRATION_PENDING']);

        $this->assertSame(0, $account->fresh()->balance_xof);
        $tx = $account->transactions()->latest()->first();
        $this->assertSame('cashIn', $tx->type->value);
        $this->assertSame('failed', $tx->status->value);
        $this->assertSame(5000, $tx->amount_xof);
    }

    public function test_cashin_bank_never_fakes_success(): void
    {
        $account = $this->account('+221771111111');

        $this->postJson('/api/cashin/bank', ['amountXof' => 5000], $this->authHeader($account))
            ->assertStatus(400)
            ->assertJson(['code' => 'INTEGRATION_PENDING']);

        $this->assertSame('failed', $account->transactions()->latest()->first()->status->value);
    }

    public function test_cashin_paypal_never_fakes_success(): void
    {
        $account = $this->account('+221771111111');

        $this->postJson('/api/cashin/paypal', ['amountXof' => 5000], $this->authHeader($account))
            ->assertStatus(400)
            ->assertJson(['code' => 'INTEGRATION_PENDING']);

        $this->assertSame(0, $account->fresh()->balance_xof);
        $this->assertSame('failed', $account->transactions()->latest()->first()->status->value);
    }

    public function test_cashout_debits_balance_with_valid_pin(): void
    {
        $account = $this->account('+221771111111', 10000);

        $this->postJson('/api/cashout', [
            'providerId' => 'orange-money', 'amountXof' => 4000, 'pin' => '123456',
        ], $this->authHeader($account))->assertStatus(202);

        $this->assertSame(6000, $account->fresh()->balance_xof);
    }

    public function test_cashout_rejects_insufficient_funds(): void
    {
        $account = $this->account('+221771111111', 100);

        $this->postJson('/api/cashout', [
            'providerId' => 'orange-money', 'amountXof' => 4000, 'pin' => '123456',
        ], $this->authHeader($account))->assertStatus(402);
    }

    public function test_pay_merchant_never_fakes_success(): void
    {
        $account = $this->account('+221771111111');
        $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 10000]);

        $this->postJson('/api/foreign-balances/pay-merchant', [
            'currencyCode' => 'EUR', 'amountMinorUnits' => 1000, 'merchantId' => 'M-1', 'pin' => '123456',
        ], $this->authHeader($account))
            ->assertStatus(400)
            ->assertJson(['code' => 'INTEGRATION_PENDING']);

        $this->assertSame(10000, $account->foreignBalances()->first()->amount_minor_units);
    }

    public function test_convert_from_xof_uses_exchange_rate(): void
    {
        ExchangeRate::create(['currency_code' => 'EUR', 'name' => 'Euro', 'rate_to_xof' => 655.957]);
        $account = $this->account('+221771111111', 100000);

        $response = $this->postJson('/api/foreign-balances/convert-from-xof', [
            'currencyCode' => 'EUR', 'amountXof' => 6560,
        ], $this->authHeader($account))->assertStatus(202)->json();

        $this->assertSame('foreignExchangeOut', $response['type']);
        $this->assertSame(93440, $account->fresh()->balance_xof);
        $this->assertSame(1000, $account->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_convert_to_xof_credits_main_balance(): void
    {
        ExchangeRate::create(['currency_code' => 'EUR', 'name' => 'Euro', 'rate_to_xof' => 655.957]);
        $account = $this->account('+221771111111', 0);
        $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 1000]);

        $this->postJson('/api/foreign-balances/convert-to-xof', [
            'currencyCode' => 'EUR', 'amountMinorUnits' => 1000,
        ], $this->authHeader($account))->assertStatus(202);

        $this->assertSame(0, $account->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
        $this->assertSame(6560, $account->fresh()->balance_xof);
    }

    public function test_ipchange_conversions_apply_the_admin_configured_fee(): void
    {
        FeeRule::create(['scope' => 'foreignExchange', 'type' => 'fixed', 'value' => 100]);
        ExchangeRate::create(['currency_code' => 'EUR', 'name' => 'Euro', 'rate_to_xof' => 655.957]);
        $account = $this->account('+221771111111', 100000);

        // Aller : les frais s'ajoutent au débit du solde principal.
        $out = $this->postJson('/api/foreign-balances/convert-from-xof', [
            'currencyCode' => 'EUR', 'amountXof' => 6560,
        ], $this->authHeader($account))->assertStatus(202)->json();
        $this->assertSame(-6660, $out['amountXof']);
        $this->assertSame(93340, $account->fresh()->balance_xof);
        $this->assertSame(1000, $account->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);

        auth()->forgetGuards();

        // Retour : les frais sont retenus sur le montant XOF crédité.
        $in = $this->postJson('/api/foreign-balances/convert-to-xof', [
            'currencyCode' => 'EUR', 'amountMinorUnits' => 1000,
        ], $this->authHeader($account))->assertStatus(202)->json();
        $this->assertSame(6460, $in['amountXof']);
    }

    public function test_send_foreign_balance_moves_between_accounts(): void
    {
        $sender = $this->account('+221771111111');
        $sender->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 5000]);
        $recipient = $this->account('+221772222222');

        $this->postJson('/api/foreign-balances/send', [
            'currencyCode' => 'EUR', 'amountMinorUnits' => 2000,
            'recipientPhoneNumber' => '+221772222222', 'pin' => '123456',
        ], $this->authHeader($sender))->assertStatus(202);

        $this->assertSame(3000, $sender->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
        $this->assertSame(2000, $recipient->foreignBalances()->where('currency_code', 'EUR')->first()->amount_minor_units);
    }

    public function test_foreign_balances_index_returns_only_own_balances(): void
    {
        $account = $this->account('+221771111111');
        $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 1000]);
        $other = $this->account('+221772222222');
        $other->foreignBalances()->create(['currency_code' => 'USD', 'amount_minor_units' => 500]);

        $json = $this->getJson('/api/foreign-balances', $this->authHeader($account))->assertOk()->json();

        $this->assertCount(1, $json);
        $this->assertSame('EUR', $json[0]['currencyCode']);
    }
}
