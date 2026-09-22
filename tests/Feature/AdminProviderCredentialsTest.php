<?php

namespace Tests\Feature;

use App\Filament\Resources\BillProviderResource\Pages\EditBillProvider;
use App\Filament\Resources\CreditOperatorResource\Pages\CreateCreditOperator;
use App\Filament\Resources\ExchangeRateResource\Pages\EditExchangeRate;
use App\Models\Account;
use App\Models\BillProvider;
use App\Models\CreditOperator;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Vérifie que les trois fiches "prestataire" (Factures, Opérateurs
// crédit, Taux de change) fonctionnent réellement de bout en bout côté
// admin — pas seulement que la page se charge (déjà couvert par
// AdminPanelSmokeTest), mais qu'un vrai remplissage + soumission du
// formulaire persiste bien les données, notamment les identifiants API
// chiffrés (ApiCredentialsFormSection, partagée par les trois).
class AdminProviderCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_editing_a_bill_provider_persists_encrypted_api_credentials(): void
    {
        // Les 5 fournisseurs de factures sont amorcés par migration (voir
        // BillProviderType) — un admin les édite pour y ajouter des
        // identifiants, il n'en crée jamais de nouveaux (l'enum est fermé).
        $provider = BillProvider::where('type', 'senelec')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(EditBillProvider::class, ['record' => $provider->type])
            ->fillForm([
                'api_base_url' => 'https://api.senelec.example',
                'api_key' => 'test-key',
                'api_secret' => 'test-secret',
                'is_live' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $provider->refresh();
        $this->assertSame('test-key', $provider->api_key);
        $this->assertSame('https://api.senelec.example', $provider->api_base_url);
        $this->assertTrue($provider->isConfigured());
    }

    public function test_creating_a_credit_operator_persists_encrypted_api_credentials(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCreditOperator::class)
            ->fillForm([
                'name' => 'Orange',
                'is_available' => true,
                'api_key' => 'orange-key',
                'api_secret' => 'orange-secret',
                'is_live' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $operator = CreditOperator::where('name', 'Orange')->first();
        $this->assertNotNull($operator);
        $this->assertSame('orange-key', $operator->api_key);
        $this->assertTrue($operator->isConfigured());
    }

    public function test_editing_an_exchange_rate_through_the_admin_form_updates_the_public_endpoint(): void
    {
        // Amorcé par migration (voir seed_exchange_rates_catalog) — un
        // admin édite le taux existant, il n'en crée pas un nouveau pour
        // une devise déjà au catalogue.
        $rate = ExchangeRate::where('currency_code', 'EUR')->firstOrFail();
        $account = Account::create(['phone_number' => '+221770001234']);

        Livewire::actingAs($this->admin)
            ->test(EditExchangeRate::class, ['record' => $rate->currency_code])
            ->fillForm(['rate_to_xof' => 700])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('700.0000', $rate->fresh()->rate_to_xof);

        // Le formulaire admin et l'endpoint que l'app consomme doivent
        // rester la même source de vérité — voir la correction du taux
        // de change de cette session.
        $json = $this->getJson(
            '/api/exchange-rates',
            ['Authorization' => 'Bearer '.$account->createToken('t')->plainTextToken],
        )->assertOk()->json();
        $eur = collect($json)->firstWhere('currencyCode', 'EUR');
        $this->assertEquals(700, $eur['rateToXof']);
    }
}
