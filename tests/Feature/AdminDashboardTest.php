<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BillProvider;
use App\Models\CreditOperator;
use App\Models\FinancialInstitution;
use App\Models\Merchant;
use App\Models\MobileMoneyProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Exigence produit : le tableau de bord doit contenir le nombre de
// clients, les partenaires, des diagrammes d'utilisation, les
// transactions et des statistiques — remplace les deux widgets
// d'exemple Filament (compte connecté, version du framework).
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_real_data_across_all_widgets(): void
    {
        $admin = User::factory()->create();

        $account = Account::create(['phone_number' => '+221770000080']);
        $account->balance_xof = 15000;
        $account->save();
        $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);
        $account->transactions()->create([
            'type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 5000, 'reference' => 'TX-DASH-1',
        ]);

        MobileMoneyProvider::create([
            'name' => 'Orange Money', 'min_amount_xof' => 500, 'max_amount_xof' => 2000000,
            'flow' => 'ussd', 'country_dial_code' => '+221', 'currency_code' => 'XOF',
        ]);
        BillProvider::firstOrCreate(['type' => 'canalPlus'], ['name' => 'Canal+']);
        FinancialInstitution::create(['name' => 'Ecobank', 'type' => 'bank']);
        CreditOperator::create(['name' => 'Orange']);
        Merchant::create(['name' => 'Boutique Awa', 'identifier_type' => 'phoneNumber', 'identifier_value' => '+221771112233']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_dashboard_renders_with_an_empty_database(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
