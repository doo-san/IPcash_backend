<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\BillProvider;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Marchand, QR et paiement de facture : aucun fournisseur réel n'est
// intégré (Wave, Orange Money, SENELEC, Woyofal, SEN'EAU — CLAUDE.md
// règle 9), donc chaque paiement renvoie INTEGRATION_PENDING. Les comptes
// factures enregistrés et la résolution d'un marchand (recherche dans le
// répertoire admin, aucun mouvement d'argent) sont de vraies opérations
// internes.
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function account(int $balance = 0): Account
    {
        $account = Account::create(['phone_number' => '+2217715'.random_int(10000, 99999)]);
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

    public function test_resolve_merchant_finds_a_real_catalog_entry(): void
    {
        $account = $this->account();
        Merchant::create(['name' => 'Boutique Awa', 'identifier_type' => 'phoneNumber', 'identifier_value' => '+221771112233']);

        $json = $this->postJson('/api/payments/merchants/resolve', [
            'identifierType' => 'phoneNumber', 'identifierValue' => '+221771112233',
        ], $this->authHeader($account))->assertOk()->json();

        $this->assertSame('Boutique Awa', $json['name']);
    }

    public function test_resolve_merchant_returns_not_found_for_an_unknown_identifier(): void
    {
        $account = $this->account();

        $this->postJson('/api/payments/merchants/resolve', [
            'identifierType' => 'phoneNumber', 'identifierValue' => '+221779999999',
        ], $this->authHeader($account))->assertStatus(404);
    }

    public function test_pay_merchant_checks_pin_then_returns_pending(): void
    {
        $account = $this->account(10000);

        $this->postJson('/api/payments/merchants/pay', [
            'merchantId' => 'm-1', 'operator' => 'orangeMoney', 'amountXof' => 1000, 'pin' => 'wrong',
        ], $this->idempotentHeader($account))->assertStatus(401);

        auth()->forgetGuards();
        $this->postJson('/api/payments/merchants/pay', [
            'merchantId' => 'm-1', 'operator' => 'orangeMoney', 'amountXof' => 1000, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);

        $tx = $account->transactions()->latest()->first();
        $this->assertSame('merchantPayment', $tx->type->value);
        $this->assertSame('failed', $tx->status->value);
        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_decode_and_confirm_qr_return_pending(): void
    {
        $account = $this->account(10000);

        $this->postJson('/api/payments/qr/decode', ['qrData' => 'raw-qr-payload'], $this->authHeader($account))
            ->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);

        $this->postJson('/api/payments/qr/confirm', [
            'reference' => 'ref-1', 'amountXof' => 1000, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);
    }

    public function test_pay_bill_checks_balance_then_returns_pending(): void
    {
        $account = $this->account(500);

        $this->postJson('/api/payments/bills/pay', [
            'provider' => 'woyofal', 'accountNumber' => '7042194024', 'amountXof' => 5000, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(402);

        auth()->forgetGuards();
        $account->balance_xof = 10000;
        $account->save();
        $this->postJson('/api/payments/bills/pay', [
            'provider' => 'woyofal', 'accountNumber' => '7042194024', 'amountXof' => 5000, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);

        $tx = $account->transactions()->latest()->first();
        $this->assertSame('billPayment', $tx->type->value);
        $this->assertSame('failed', $tx->status->value);
        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_add_and_list_bill_accounts_scoped_by_provider(): void
    {
        $account = $this->account();

        $created = $this->postJson('/api/payments/bills/woyofal/accounts', [
            'nickname' => 'Compteur maison', 'accountNumber' => '7042194024',
        ], $this->authHeader($account))->assertStatus(201)->json();
        $this->assertSame('Compteur maison', $created['nickname']);

        $this->postJson('/api/payments/bills/canalPlus/accounts', [
            'nickname' => 'Décodeur', 'accountNumber' => '2420018920',
        ], $this->authHeader($account))->assertStatus(201);

        $woyofalAccounts = $this->getJson('/api/payments/bills/woyofal/accounts', $this->authHeader($account))->assertOk()->json();
        $this->assertCount(1, $woyofalAccounts);
        $this->assertSame('7042194024', $woyofalAccounts[0]['accountNumber']);
    }

    public function test_unknown_bill_provider_is_not_found(): void
    {
        $account = $this->account();

        $this->getJson('/api/payments/bills/not-a-provider/accounts', $this->authHeader($account))->assertStatus(404);
    }

    public function test_bill_providers_lists_only_active_ones(): void
    {
        $account = $this->account();

        BillProvider::where('type', 'rapido')->update(['is_active' => false]);

        $json = $this->getJson('/api/payments/bills/providers', $this->authHeader($account))->assertOk()->json();

        $this->assertCount(4, $json);
        $this->assertNotContains('rapido', array_column($json, 'type'));
    }

    public function test_deactivating_a_bill_provider_in_admin_blocks_the_app(): void
    {
        $account = $this->account();

        BillProvider::where('type', 'woyofal')->update(['is_active' => false]);

        $this->getJson('/api/payments/bills/woyofal/accounts', $this->authHeader($account))->assertStatus(404);
    }

    public function test_lookup_bill_invoice_returns_pending(): void
    {
        $account = $this->account();

        $this->postJson('/api/payments/bills/senelec/invoice', [
            'customerReference' => '123456',
        ], $this->authHeader($account))->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);
    }
}
