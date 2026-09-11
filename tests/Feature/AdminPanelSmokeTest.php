<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Filament\Resources\AccountResource\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\KycDocumentResource\Pages\ListKycDocuments;
use App\Models\Account;
use App\Models\BillProvider;
use App\Models\CreditOperator;
use App\Models\EsimPlan;
use App\Models\ExchangeRate;
use App\Models\InsurancePlan;
use App\Models\Merchant;
use App\Models\MobileMoneyProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Test de fumée permanent : garantit qu'aucune ressource Filament ne
// contient d'erreur PHP qui ne se révélerait qu'à l'exécution réelle
// (Livewire ne peut pas être exercé par un simple curl — voir le login en
// AJAX). Couvre chaque page index/vue/édition avec au moins un
// enregistrement en base.
class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_dashboard_loads(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_account_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000001']);

        $this->actingAs($this->admin)->get('/admin/accounts')->assertOk();
        $this->actingAs($this->admin)->get("/admin/accounts/{$account->id}")->assertOk();
        $this->actingAs($this->admin)->get("/admin/accounts/{$account->id}/edit")->assertOk();
    }

    public function test_kyc_document_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000002']);
        $document = $account->kycDocuments()->create([
            'document_type' => 'nationalId',
            'front_path' => 'kyc/front.jpg',
            'selfie_path' => 'kyc/selfie.jpg',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/admin/kyc-documents')->assertOk();
        $this->actingAs($this->admin)->get("/admin/kyc-documents/{$document->id}")->assertOk();
    }

    public function test_transaction_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000003']);
        $transaction = $account->transactions()->create([
            'type' => 'transferIn',
            'status' => 'completed',
            'amount_xof' => 1000,
            'reference' => 'TX-SMOKE-1',
        ]);

        $this->actingAs($this->admin)->get('/admin/transactions')->assertOk();
        $this->actingAs($this->admin)->get("/admin/transactions/{$transaction->id}")->assertOk();
    }

    public function test_card_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000004']);
        $card = $account->cards()->create([
            'last4' => '1234',
            'expiry_month' => 9,
            'expiry_year' => 2028,
        ]);

        $this->actingAs($this->admin)->get('/admin/cards')->assertOk();
        $this->actingAs($this->admin)->get("/admin/cards/{$card->id}/edit")->assertOk();
    }

    public function test_mobile_money_provider_pages_load(): void
    {
        $provider = MobileMoneyProvider::create([
            'name' => 'Orange Money',
            'min_amount_xof' => 500,
            'max_amount_xof' => 2000000,
            'flow' => 'ussd',
            'country_dial_code' => '+221',
            'currency_code' => 'XOF',
        ]);

        $this->actingAs($this->admin)->get('/admin/mobile-money-providers')->assertOk();
        $this->actingAs($this->admin)->get('/admin/mobile-money-providers/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/mobile-money-providers/{$provider->id}/edit")->assertOk();
    }

    public function test_foreign_balance_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000007']);
        $balance = $account->foreignBalances()->create([
            'currency_code' => 'EUR',
            'amount_minor_units' => 1000,
        ]);

        $this->actingAs($this->admin)->get('/admin/foreign-balances')->assertOk();
        $this->actingAs($this->admin)->get("/admin/foreign-balances/{$balance->id}")->assertOk();
    }

    public function test_chat_message_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000008']);
        $message = $account->chatMessages()->create(['role' => 'user', 'text' => 'Bonjour']);

        $this->actingAs($this->admin)->get('/admin/chat-messages')->assertOk();
        $this->actingAs($this->admin)->get("/admin/chat-messages/{$message->id}")->assertOk();
    }

    public function test_exchange_rate_pages_load(): void
    {
        $rate = ExchangeRate::create([
            'currency_code' => 'EUR',
            'name' => 'Euro',
            'flag' => '🇪🇺',
            'rate_to_xof' => 655.957,
        ]);

        $this->actingAs($this->admin)->get('/admin/exchange-rates')->assertOk();
        $this->actingAs($this->admin)->get('/admin/exchange-rates/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/exchange-rates/{$rate->currency_code}/edit")->assertOk();
    }

    public function test_esim_plan_pages_load(): void
    {
        $plan = EsimPlan::create([
            'scope' => 'local',
            'data_gb' => 1,
            'validity_days' => 7,
            'price_xof' => 1500,
        ]);

        $this->actingAs($this->admin)->get('/admin/esim-plans')->assertOk();
        $this->actingAs($this->admin)->get('/admin/esim-plans/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/esim-plans/{$plan->id}/edit")->assertOk();
    }

    public function test_insurance_plan_pages_load(): void
    {
        $plan = InsurancePlan::create([
            'insurance_type' => 'auto',
            'duration_months' => 3,
            'price_xof' => 15000,
        ]);

        $this->actingAs($this->admin)->get('/admin/insurance-plans')->assertOk();
        $this->actingAs($this->admin)->get('/admin/insurance-plans/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/insurance-plans/{$plan->id}/edit")->assertOk();
    }

    public function test_credit_operator_pages_load(): void
    {
        $operator = CreditOperator::create(['name' => 'Orange']);

        $this->actingAs($this->admin)->get('/admin/credit-operators')->assertOk();
        $this->actingAs($this->admin)->get('/admin/credit-operators/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/credit-operators/{$operator->id}/edit")->assertOk();
    }

    public function test_merchant_pages_load(): void
    {
        $merchant = Merchant::create([
            'name' => 'Boutique Test',
            'identifier_type' => 'phoneNumber',
            'identifier_value' => '+221770000000',
        ]);

        $this->actingAs($this->admin)->get('/admin/merchants')->assertOk();
        $this->actingAs($this->admin)->get('/admin/merchants/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/merchants/{$merchant->id}/edit")->assertOk();
    }

    public function test_bill_provider_pages_load(): void
    {
        $provider = BillProvider::firstOrCreate(['type' => 'senelec'], ['name' => 'Senelec']);

        $this->actingAs($this->admin)->get('/admin/bill-providers')->assertOk();
        $this->actingAs($this->admin)->get('/admin/bill-providers/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/bill-providers/{$provider->type}/edit")->assertOk();
    }

    public function test_pocket_pages_load(): void
    {
        $account = Account::create(['phone_number' => '+221770000009']);
        $pocket = $account->pockets()->create(['name' => 'Vacances', 'balance_xof' => 5000]);

        $this->actingAs($this->admin)->get('/admin/pockets')->assertOk();
        $this->actingAs($this->admin)->get("/admin/pockets/{$pocket->id}")->assertOk();
    }

    public function test_sessions_relation_manager_lists_and_can_revoke(): void
    {
        $account = Account::create(['phone_number' => '+221770000012']);
        $token = $account->refreshTokens()->create([
            'device_id' => 'device-test',
            'token_hash' => bcrypt('raw-token'),
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($this->admin)
            ->test(SessionsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->assertCanSeeTableRecords([$token])
            ->callTableAction('revoke', $token);

        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_kyc_approve_action_updates_document_and_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000005']);
        $account->kyc_status = 'pending';
        $account->save();

        $document = $account->kycDocuments()->create([
            'document_type' => 'nationalId',
            'front_path' => 'kyc/front.jpg',
            'selfie_path' => 'kyc/selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListKycDocuments::class)
            ->callTableAction('approve', $document);

        $this->assertSame('verified', $document->fresh()->status->value);
        $this->assertSame($this->admin->id, $document->fresh()->reviewed_by);
        $this->assertSame('verified', $account->fresh()->kyc_status->value);
    }

    public function test_kyc_reject_action_records_reason_on_document_and_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000006']);
        $account->kyc_status = 'pending';
        $account->save();

        $document = $account->kycDocuments()->create([
            'document_type' => 'nationalId',
            'front_path' => 'kyc/front.jpg',
            'selfie_path' => 'kyc/selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListKycDocuments::class)
            ->callTableAction('reject', $document, data: ['reason' => 'Photo illisible']);

        $this->assertSame('rejected', $document->fresh()->status->value);
        $this->assertSame('Photo illisible', $document->fresh()->rejection_reason);
        $this->assertSame('rejected', $account->fresh()->kyc_status->value);
        $this->assertSame('Photo illisible', $account->fresh()->kyc_rejection_reason);
    }
}
