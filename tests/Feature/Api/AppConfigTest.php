<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AppSetting;
use App\Models\MobileMoneyProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Réglages de l'application édités depuis l'admin (AppConfigPage) et leur
// effet réel sur l'API : GET /config, montant minimum, maintenance, services
// désactivés, limites des opérateurs.
class AppConfigTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $phone, int $balance = 100000): Account
    {
        $account = Account::create(['phone_number' => $phone]);
        $account->pin_hash = bcrypt('123456');
        $account->kyc_status = 'verified';
        $account->balance_xof = $balance;
        $account->save();

        return $account;
    }

    private function headers(Account $account): array
    {
        return [
            'Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken,
            'Idempotency-Key' => Str::uuid()->toString(),
        ];
    }

    private function set(string $key, string $value): void
    {
        AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function test_config_is_public_and_returns_defaults(): void
    {
        $this->getJson('/api/config')
            ->assertOk()
            ->assertJson([
                'supportPhoneNumber' => '+221338000000',
                'maintenance' => ['enabled' => false, 'message' => null],
                'minAppVersion' => ['ios' => null, 'android' => null],
                'limits' => ['transferMinAmountXof' => 5],
                'features' => [
                    'transfer' => true, 'cashio' => true, 'cashchange' => true, 'payment' => true,
                    'credit' => true, 'esim' => true, 'insurance' => true, 'card' => true, 'pockets' => true,
                ],
            ]);
    }

    public function test_config_reflects_admin_values(): void
    {
        $this->set('support_phone_number', '+221771234567');
        $this->set('min_app_version_ios', '1.4.0');
        $this->set('feature_esim_enabled', '0');
        $this->set('maintenance_enabled', '1');
        $this->set('maintenance_message', 'Retour à 18h.');

        $this->getJson('/api/config')->assertOk()->assertJson([
            'supportPhoneNumber' => '+221771234567',
            'minAppVersion' => ['ios' => '1.4.0', 'android' => null],
            'features' => ['esim' => false, 'transfer' => true],
            'maintenance' => ['enabled' => true, 'message' => 'Retour à 18h.'],
        ]);
    }

    public function test_transfer_minimum_amount_follows_the_admin_setting(): void
    {
        $this->set('transfer_min_amount_xof', '100');
        $sender = $this->account('+221771111111');
        $this->account('+221772222222');

        $this->postJson('/api/transfers/quote', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 99,
        ], $this->headers($sender))->assertStatus(422);

        $this->postJson('/api/transfers/quote', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 100,
        ], $this->headers($sender))->assertOk();
    }

    public function test_a_disabled_service_answers_403_on_its_routes_only(): void
    {
        $this->set('feature_credit_enabled', '0');
        $account = $this->account('+221771111111');

        $this->getJson('/api/credit/operators', $this->headers($account))
            ->assertStatus(403)
            ->assertJson(['code' => 'FEATURE_DISABLED']);

        $this->getJson('/api/insurance/plans', $this->headers($account))->assertOk();
    }

    public function test_maintenance_blocks_the_authenticated_api_but_not_config(): void
    {
        $this->set('maintenance_enabled', '1');
        $account = $this->account('+221771111111');

        $this->getJson('/api/accounts/me', $this->headers($account))
            ->assertStatus(503)
            ->assertJson(['code' => 'MAINTENANCE']);

        $this->getJson('/api/config')->assertOk();
    }

    public function test_cashin_and_cashout_respect_the_operator_limits(): void
    {
        $provider = MobileMoneyProvider::create([
            'name' => 'Mixx by Yas', 'min_amount_xof' => 500, 'max_amount_xof' => 300000,
            'flow' => 'ussd', 'country_dial_code' => '+221', 'currency_code' => 'XOF',
        ]);
        $account = $this->account('+221771111111');

        $this->postJson('/api/cashin', [
            'providerId' => $provider->id, 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 499,
        ], $this->headers($account))->assertStatus(422)->assertJson(['code' => 'AMOUNT_OUT_OF_RANGE']);

        $this->postJson('/api/cashout', [
            'providerId' => $provider->id, 'amountXof' => 300001, 'pin' => '123456',
        ], $this->headers($account))->assertStatus(422)->assertJson(['code' => 'AMOUNT_OUT_OF_RANGE']);

        $this->postJson('/api/cashin', [
            'providerId' => $provider->id, 'sourcePhoneNumber' => '+221771111111', 'amountXof' => 500,
        ], $this->headers($account))->assertStatus(202);
    }
}
