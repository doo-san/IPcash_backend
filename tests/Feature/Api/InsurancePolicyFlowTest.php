<?php

namespace Tests\Feature\Api;

use App\Enums\KycVerificationStatus;
use App\Models\Account;
use App\Models\AppSetting;
use App\Models\FeeRule;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Services\Insurance\InsurancePolicyDecisionException;
use App\Services\Insurance\InsurancePolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Souscription d'assurance de bout en bout : paiement depuis le solde →
// contrat `pending` → validation admin (attestation PDF) ou rejet
// (remboursement). Le prix vient toujours du catalogue, jamais du client.
class InsurancePolicyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('insurance');
        InsurancePlan::create(['insurance_type' => 'auto', 'duration_months' => 6, 'price_xof' => 25000]);
        InsurancePlan::create(['insurance_type' => 'moto', 'duration_months' => 3, 'price_xof' => 8000]);
    }

    private function account(int $balance = 100000, bool $kycVerified = true): Account
    {
        $account = Account::create(['phone_number' => '+2217715'.random_int(10000, 99999)]);
        $account->pin_hash = bcrypt('123456');
        $account->balance_xof = $balance;
        $account->first_name = 'Awa';
        $account->last_name = 'Diop';
        $account->kyc_status = $kycVerified ? KycVerificationStatus::Verified : KycVerificationStatus::Pending;
        $account->save();

        return $account;
    }

    // Créer un compte journalise l'activité (LogsAdminActivity → auth()->user()),
    // ce qui fige dans le garde Sanctum l'utilisateur de la requête de test
    // précédente : on repart d'un garde vierge avant chaque requête.
    private function headers(Account $account, ?string $key = null): array
    {
        auth()->forgetGuards();

        return [
            'Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken,
            'Idempotency-Key' => $key ?? Str::uuid()->toString(),
        ];
    }

    private function autoPayload(array $overrides = []): array
    {
        return [
            'type' => 'auto',
            'durationMonths' => 6,
            'plateNumber' => 'dk 1234 ab',
            'vehicleBrand' => 'Toyota',
            'vehicleUsage' => 'personal',
            'horsepower' => 7,
            'seats' => 5,
            'ageBracket' => 'fiveToTen',
            'pin' => '123456',
            ...$overrides,
        ];
    }

    private function fillInsuranceSettings(): void
    {
        foreach ([
            'insurance_insurer_name' => 'Compagnie Test',
            'insurance_intermediary_name' => 'IPCash SA',
            'insurance_agreement_number' => 'AGR-0001',
            'insurance_guarantees' => 'Responsabilité civile',
        ] as $key => $value) {
            AppSetting::create(['key' => $key, 'value' => $value]);
        }
    }

    private function reviewer(): User
    {
        return User::factory()->create();
    }

    public function test_purchase_debits_the_catalogue_price_and_creates_a_pending_policy(): void
    {
        $account = $this->account(100000);

        $json = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))
            ->assertStatus(202)->json();

        $this->assertSame('pending', $json['status']);
        $this->assertSame(25000, $json['priceXof']);
        $this->assertSame('DK1234AB', $json['plateNumber']);
        $this->assertNull($json['policyNumber']);
        $this->assertFalse($json['hasAttestation']);
        $this->assertSame(75000, $account->fresh()->balance_xof);

        $tx = $account->transactions()->first();
        $this->assertSame('insurancePurchase', $tx->type->value);
        $this->assertSame('completed', $tx->status->value);
        $this->assertSame(-25000, $tx->amount_xof);
    }

    public function test_a_client_supplied_amount_is_ignored(): void
    {
        $account = $this->account(100000);

        $this->postJson('/api/insurance/purchase', $this->autoPayload(['amountXof' => 1]), $this->headers($account))
            ->assertStatus(202)->assertJson(['priceXof' => 25000]);

        $this->assertSame(75000, $account->fresh()->balance_xof);
    }

    public function test_the_fee_is_debited_on_top_of_the_price(): void
    {
        FeeRule::create(['scope' => 'insurance', 'type' => 'fixed', 'value' => 2000]);
        $account = $this->account(100000);

        $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))
            ->assertStatus(202)->assertJson(['feeXof' => 2000]);

        $this->assertSame(73000, $account->fresh()->balance_xof);
    }

    public function test_insufficient_balance_is_rejected_without_any_side_effect(): void
    {
        $account = $this->account(1000);

        $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))
            ->assertStatus(402);

        $this->assertSame(1000, $account->fresh()->balance_xof);
        $this->assertSame(0, InsurancePolicy::count());
        $this->assertSame(0, $account->transactions()->count());
    }

    public function test_wrong_pin_and_missing_kyc_are_rejected(): void
    {
        $account = $this->account();
        $this->postJson('/api/insurance/purchase', $this->autoPayload(['pin' => '000000']), $this->headers($account))
            ->assertStatus(401);

        auth()->forgetGuards();
        $unverified = $this->account(kycVerified: false);
        $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($unverified))
            ->assertStatus(403)->assertJson(['code' => 'KYC_REQUIRED']);

        $this->assertSame(0, InsurancePolicy::count());
    }

    public function test_an_unknown_or_inactive_plan_is_rejected(): void
    {
        $account = $this->account();

        $this->postJson('/api/insurance/purchase', $this->autoPayload(['durationMonths' => 12]), $this->headers($account))
            ->assertStatus(422);

        $this->assertSame(100000, $account->fresh()->balance_xof);
    }

    public function test_auto_requires_its_vehicle_fields_and_moto_its_own(): void
    {
        $account = $this->account();

        $this->postJson('/api/insurance/purchase', $this->autoPayload(['horsepower' => null]), $this->headers($account))
            ->assertStatus(422);

        auth()->forgetGuards();
        $this->postJson('/api/insurance/purchase', [
            'type' => 'moto', 'durationMonths' => 3, 'plateNumber' => 'DK-5678-C',
            'vehicleUsage' => 'professional', 'bodyType' => 'triporteur', 'displacementCc' => 125, 'pin' => '123456',
        ], $this->headers($account))->assertStatus(202)->assertJson(['priceXof' => 8000]);
    }

    public function test_a_replayed_idempotency_key_returns_the_same_policy_without_debiting_twice(): void
    {
        $account = $this->account(100000);
        $key = Str::uuid()->toString();

        $first = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account, $key))
            ->assertStatus(202)->json();
        auth()->forgetGuards();
        $second = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account, $key))
            ->assertStatus(202)->json();

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(1, InsurancePolicy::count());
        $this->assertSame(75000, $account->fresh()->balance_xof);
    }

    public function test_policies_are_scoped_to_their_owner(): void
    {
        $owner = $this->account();
        $policyId = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($owner))->json('id');

        auth()->forgetGuards();
        $other = $this->account();
        $this->getJson("/api/insurance/policies/{$policyId}", $this->headers($other))->assertNotFound();
        $this->getJson("/api/insurance/policies/{$policyId}/attestation", $this->headers($other))->assertNotFound();
        $this->assertCount(0, $this->getJson('/api/insurance/policies', $this->headers($other))->json());

        auth()->forgetGuards();
        $this->assertCount(1, $this->getJson('/api/insurance/policies', $this->headers($owner))->json());
    }

    public function test_the_attestation_is_unavailable_while_pending(): void
    {
        $account = $this->account();
        $policyId = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->json('id');

        $this->getJson("/api/insurance/policies/{$policyId}/attestation", $this->headers($account))
            ->assertNotFound()->assertJson(['code' => 'ATTESTATION_NOT_AVAILABLE']);
    }

    public function test_approval_is_refused_until_the_attestation_mentions_are_filled(): void
    {
        $account = $this->account();
        $policyId = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->json('id');

        $this->expectException(InsurancePolicyDecisionException::class);
        try {
            app(InsurancePolicyService::class)->approve(InsurancePolicy::find($policyId), $this->reviewer());
        } finally {
            $this->assertSame('pending', InsurancePolicy::find($policyId)->status->value);
        }
    }

    public function test_approval_activates_the_policy_and_the_owner_can_download_the_pdf(): void
    {
        $this->fillInsuranceSettings();
        $account = $this->account();
        $policyId = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->json('id');

        app(InsurancePolicyService::class)->approve(InsurancePolicy::find($policyId), $this->reviewer());

        $policy = InsurancePolicy::find($policyId);
        $this->assertSame('active', $policy->status->value);
        $this->assertMatchesRegularExpression('/^IPC-A-\d{4}-[A-Z0-9]{8}$/', $policy->policy_number);
        $this->assertSame(
            $policy->starts_at->copy()->addMonthsNoOverflow(6)->subDay()->toDateString(),
            $policy->ends_at->toDateString(),
        );
        Storage::disk('insurance')->assertExists($policy->attestation_path);

        auth()->forgetGuards();
        $response = $this->get("/api/insurance/policies/{$policyId}/attestation", $this->headers($account))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_a_policy_cannot_be_decided_twice(): void
    {
        $this->fillInsuranceSettings();
        $account = $this->account();
        $policyId = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->json('id');
        $service = app(InsurancePolicyService::class);
        $service->approve(InsurancePolicy::find($policyId), $this->reviewer());

        $this->expectException(InsurancePolicyDecisionException::class);
        $service->reject(InsurancePolicy::find($policyId), $this->reviewer(), 'Trop tard');
    }

    public function test_rejection_refunds_price_and_fee_once(): void
    {
        FeeRule::create(['scope' => 'insurance', 'type' => 'fixed', 'value' => 2000]);
        $account = $this->account(100000);
        $policyId = $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->json('id');
        $this->assertSame(73000, $account->fresh()->balance_xof);

        app(InsurancePolicyService::class)->reject(InsurancePolicy::find($policyId), $this->reviewer(), 'Immatriculation invalide');

        $policy = InsurancePolicy::find($policyId);
        $this->assertSame('rejected', $policy->status->value);
        $this->assertSame('Immatriculation invalide', $policy->rejection_reason);
        $this->assertSame(100000, $account->fresh()->balance_xof);
        $this->assertSame('insuranceRefund', $policy->refundTransaction->type->value);
        $this->assertSame(27000, $policy->refundTransaction->amount_xof);
    }

    public function test_policies_stay_visible_when_new_subscriptions_are_closed(): void
    {
        $account = $this->account();
        $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->assertStatus(202);
        AppSetting::create(['key' => 'feature_insurance_enabled', 'value' => '0']);

        auth()->forgetGuards();
        $this->postJson('/api/insurance/purchase', $this->autoPayload(), $this->headers($account))->assertStatus(403);
        auth()->forgetGuards();
        $this->getJson('/api/insurance/policies', $this->headers($account))->assertOk()->assertJsonCount(1);
    }
}
