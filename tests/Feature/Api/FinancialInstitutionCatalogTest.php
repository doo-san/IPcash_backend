<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\FinancialInstitution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// `BankLinkScreen` utilisait jusqu'ici une liste codée en dur côté
// Flutter — ce catalogue admin (banques/IMF) est maintenant réellement
// exposé à l'app.
class FinancialInstitutionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_institutions_with_logo_url(): void
    {
        $account = Account::create(['phone_number' => '+221770000040']);
        FinancialInstitution::create(['name' => 'Ecobank', 'type' => 'bank', 'logo_url' => 'logos/bank/ecobank.png']);
        FinancialInstitution::create(['name' => 'ACEP', 'type' => 'microfinance']);
        FinancialInstitution::create(['name' => 'Fermée', 'type' => 'bank', 'is_active' => false]);

        $json = $this->getJson('/api/providers/financial-institutions', [
            'Authorization' => 'Bearer '.$account->createToken('t')->plainTextToken,
        ])->assertOk()->json();

        $this->assertCount(2, $json);
        $names = array_column($json, 'name');
        $this->assertContains('Ecobank', $names);
        $this->assertContains('ACEP', $names);
        $this->assertNotContains('Fermée', $names);

        $ecobank = collect($json)->firstWhere('name', 'Ecobank');
        $this->assertStringContainsString('/storage/logos/bank/ecobank.png', $ecobank['logoUrl']);

        $acep = collect($json)->firstWhere('name', 'ACEP');
        $this->assertNull($acep['logoUrl']);
        $this->assertSame('microfinance', $acep['type']);
    }
}
