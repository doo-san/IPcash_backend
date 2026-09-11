<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\CreditOperator;
use App\Models\EsimPlan;
use App\Models\FeeRule;
use App\Models\InsurancePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Crédit, assurance et eSIM : aucun fournisseur tiers n'est réellement
// intégré (CLAUDE.md règle 9) — ces trois achats renvoient toujours
// INTEGRATION_PENDING après vérification PIN/solde, jamais une fausse
// réussite. Les catalogues (opérateurs, forfaits eSIM) restent réels.
class CreditInsuranceEsimTest extends TestCase
{
    use RefreshDatabase;

    private function account(int $balance = 0): Account
    {
        $account = Account::create(['phone_number' => '+2217714'.random_int(10000, 99999)]);
        $account->pin_hash = bcrypt('123456');
        $account->balance_xof = $balance;
        $account->save();

        return $account;
    }

    private function idempotentHeader(Account $account): array
    {
        return [
            'Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken,
            'Idempotency-Key' => Str::uuid()->toString(),
        ];
    }

    public function test_credit_operators_lists_only_available_ones_with_their_logo(): void
    {
        $account = $this->account();
        CreditOperator::create(['name' => 'Orange', 'is_available' => true, 'logo_url' => 'logos/credit-operators/orange.png']);
        CreditOperator::create(['name' => 'Injoignable', 'is_available' => false]);

        $json = $this->getJson('/api/credit/operators', ['Authorization' => 'Bearer '.$account->createToken('t')->plainTextToken])
            ->assertOk()->json();

        $this->assertCount(1, $json);
        $this->assertSame('Orange', $json[0]['name']);
        $this->assertStringContainsString('logos/credit-operators/orange.png', $json[0]['logoUrl']);
    }

    public function test_credit_operator_without_a_logo_returns_null(): void
    {
        $account = $this->account();
        CreditOperator::create(['name' => 'Sans logo', 'is_available' => true]);

        $json = $this->getJson('/api/credit/operators', ['Authorization' => 'Bearer '.$account->createToken('t')->plainTextToken])
            ->assertOk()->json();

        $this->assertNull($json[0]['logoUrl']);
    }

    public function test_credit_purchase_checks_pin_then_balance_then_returns_pending(): void
    {
        $account = $this->account(10000);
        $operator = CreditOperator::create(['name' => 'Orange', 'is_available' => true]);
        $payload = ['operatorId' => $operator->id, 'phoneNumber' => '+221770000000', 'amountXof' => 5000];

        $this->postJson('/api/credit/purchase', [...$payload, 'pin' => 'wrong-pin'], $this->idempotentHeader($account))
            ->assertStatus(401);

        auth()->forgetGuards();
        $this->postJson('/api/credit/purchase', [...$payload, 'amountXof' => 999999, 'pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(402);

        auth()->forgetGuards();
        $this->postJson('/api/credit/purchase', [...$payload, 'pin' => '123456'], $this->idempotentHeader($account))
            ->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);

        // La tentative est tracée (Transaction `failed`) sans toucher le solde.
        $tx = $account->transactions()->latest()->first();
        $this->assertSame('creditPurchase', $tx->type->value);
        $this->assertSame('failed', $tx->status->value);
        $this->assertSame(-5000, $tx->amount_xof);
        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_insurance_purchase_returns_pending_and_records_a_failed_attempt(): void
    {
        $account = $this->account(10000);

        $this->postJson('/api/insurance/purchase', [
            'type' => 'auto', 'plateNumber' => 'DK-1234-AB', 'amountXof' => 5000, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);

        $tx = $account->transactions()->latest()->first();
        $this->assertSame('insurancePurchase', $tx->type->value);
        $this->assertSame('failed', $tx->status->value);
        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_a_replayed_idempotency_key_does_not_duplicate_the_failed_attempt(): void
    {
        $account = $this->account(10000);
        $headers = $this->idempotentHeader($account);

        $this->postJson('/api/insurance/purchase', [
            'type' => 'auto', 'plateNumber' => 'DK-1234-AB', 'amountXof' => 5000, 'pin' => '123456',
        ], $headers)->assertStatus(400);

        auth()->forgetGuards();
        $this->postJson('/api/insurance/purchase', [
            'type' => 'auto', 'plateNumber' => 'DK-1234-AB', 'amountXof' => 5000, 'pin' => '123456',
        ], $headers)->assertStatus(400);

        $this->assertSame(1, $account->transactions()->count());
    }

    public function test_insurance_fee_is_included_in_the_balance_check(): void
    {
        FeeRule::create(['scope' => 'insurance', 'type' => 'fixed', 'value' => 2000]);
        $account = $this->account(6000); // couvre 5000 mais pas 5000 + 2000 de frais

        $this->postJson('/api/insurance/purchase', [
            'type' => 'auto', 'plateNumber' => 'DK-1234-AB', 'amountXof' => 5000, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(402);
    }

    public function test_insurance_plans_lists_only_active_ones(): void
    {
        $account = $this->account();
        InsurancePlan::create(['insurance_type' => 'auto', 'duration_months' => 6, 'price_xof' => 25000]);
        InsurancePlan::create(['insurance_type' => 'moto', 'duration_months' => 3, 'price_xof' => 8000, 'is_active' => false]);

        $json = $this->getJson('/api/insurance/plans', ['Authorization' => 'Bearer '.$account->createToken('t')->plainTextToken])
            ->assertOk()->json();

        $this->assertCount(1, $json);
        $this->assertSame('auto', $json[0]['insuranceType']);
        $this->assertSame(25000, $json[0]['priceXof']);
    }

    public function test_esim_activate_returns_pending_and_records_a_failed_attempt(): void
    {
        $account = $this->account(10000);

        $this->postJson('/api/esim/activate', [
            'countryName' => 'Sénégal', 'planLabel' => '5GB', 'amountXof' => 6500, 'pin' => '123456',
        ], $this->idempotentHeader($account))->assertStatus(400)->assertJson(['code' => 'INTEGRATION_PENDING']);

        $tx = $account->transactions()->latest()->first();
        $this->assertSame('esimActivation', $tx->type->value);
        $this->assertSame('failed', $tx->status->value);
        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_esim_plans_lists_only_active_ones(): void
    {
        $account = $this->account();
        EsimPlan::create(['scope' => 'local', 'data_gb' => 5, 'validity_days' => 30, 'price_xof' => 6500]);
        EsimPlan::create(['scope' => 'global', 'data_gb' => 1, 'validity_days' => 7, 'price_xof' => 4200, 'is_active' => false]);

        $json = $this->getJson('/api/esim/plans', ['Authorization' => 'Bearer '.$account->createToken('t')->plainTextToken])
            ->assertOk()->json();

        $this->assertCount(1, $json);
        $this->assertSame('local', $json[0]['scope']);
        $this->assertSame(6500, $json[0]['priceXof']);
    }
}
