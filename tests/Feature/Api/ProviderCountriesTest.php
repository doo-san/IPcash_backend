<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// GET /providers/countries — miroir de CountryResource côté admin,
// jusqu'ici sans aucun effet réel (PhoneCountry restant codé en dur côté
// app).
class ProviderCountriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_countries_ordered_by_name(): void
    {
        $account = Account::create(['phone_number' => '+221771112233']);
        $token = $account->createToken('test')->plainTextToken;

        Country::create([
            'dial_code' => '+221', 'name' => 'Sénégal', 'flag' => '🇸🇳',
            'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => ['70', '77'], 'currency_code' => 'XOF',
        ]);
        Country::create([
            'dial_code' => '+225', 'name' => "Côte d'Ivoire", 'flag' => '🇨🇮',
            'min_digits' => 10, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'XOF', 'is_active' => false,
        ]);

        $response = $this->getJson('/api/providers/countries', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json();

        $this->assertCount(1, $response);
        $this->assertSame('+221', $response[0]['dialCode']);
        $this->assertSame(['70', '77'], $response[0]['mobilePrefixes']);
    }
}
