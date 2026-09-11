<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

// Reprend telle quelle la liste de `PhoneCountry`
// (`lib/features/auth/domain/phone_country.dart` côté Flutter) — donne à
// l'admin un point de départ réel identique au comportement actuel de
// l'app plutôt qu'une table vide, avant que l'app ne bascule sur cette
// source. `updateOrCreate` : rejouable sans dupliquer si relancé.
class CountrySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->countries() as $country) {
            Country::updateOrCreate(['dial_code' => $country['dial_code']], $country);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function countries(): array
    {
        return [
            ['dial_code' => '+221', 'name' => 'Sénégal', 'flag' => '🇸🇳', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => ['70', '75', '76', '77', '78'], 'currency_code' => 'XOF'],
            ['dial_code' => '+225', 'name' => "Côte d'Ivoire", 'flag' => '🇨🇮', 'min_digits' => 10, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+229', 'name' => 'Bénin', 'flag' => '🇧🇯', 'min_digits' => 8, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+226', 'name' => 'Burkina Faso', 'flag' => '🇧🇫', 'min_digits' => 8, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+245', 'name' => 'Guinée-Bissau', 'flag' => '🇬🇼', 'min_digits' => 7, 'max_digits' => 8, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+223', 'name' => 'Mali', 'flag' => '🇲🇱', 'min_digits' => 8, 'max_digits' => 8, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+227', 'name' => 'Niger', 'flag' => '🇳🇪', 'min_digits' => 8, 'max_digits' => 8, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+228', 'name' => 'Togo', 'flag' => '🇹🇬', 'min_digits' => 8, 'max_digits' => 8, 'mobile_prefixes' => null, 'currency_code' => 'XOF'],
            ['dial_code' => '+224', 'name' => 'Guinée', 'flag' => '🇬🇳', 'min_digits' => 8, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'GNF'],
            ['dial_code' => '+222', 'name' => 'Mauritanie', 'flag' => '🇲🇷', 'min_digits' => 7, 'max_digits' => 8, 'mobile_prefixes' => null, 'currency_code' => 'MRU'],
            ['dial_code' => '+220', 'name' => 'Gambie', 'flag' => '🇬🇲', 'min_digits' => 7, 'max_digits' => 7, 'mobile_prefixes' => null, 'currency_code' => 'GMD'],
            ['dial_code' => '+233', 'name' => 'Ghana', 'flag' => '🇬🇭', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'GHS'],
            ['dial_code' => '+234', 'name' => 'Nigeria', 'flag' => '🇳🇬', 'min_digits' => 10, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'NGN'],
            ['dial_code' => '+237', 'name' => 'Cameroun', 'flag' => '🇨🇲', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'XAF'],
            ['dial_code' => '+241', 'name' => 'Gabon', 'flag' => '🇬🇦', 'min_digits' => 7, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'XAF'],
            ['dial_code' => '+212', 'name' => 'Maroc', 'flag' => '🇲🇦', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'MAD'],
            ['dial_code' => '+213', 'name' => 'Algérie', 'flag' => '🇩🇿', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'DZD'],
            ['dial_code' => '+216', 'name' => 'Tunisie', 'flag' => '🇹🇳', 'min_digits' => 8, 'max_digits' => 8, 'mobile_prefixes' => null, 'currency_code' => 'TND'],
            ['dial_code' => '+243', 'name' => 'RD Congo', 'flag' => '🇨🇩', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'CDF'],
            ['dial_code' => '+33', 'name' => 'France', 'flag' => '🇫🇷', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'EUR'],
            ['dial_code' => '+34', 'name' => 'Espagne', 'flag' => '🇪🇸', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'EUR'],
            ['dial_code' => '+39', 'name' => 'Italie', 'flag' => '🇮🇹', 'min_digits' => 9, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'EUR'],
            ['dial_code' => '+32', 'name' => 'Belgique', 'flag' => '🇧🇪', 'min_digits' => 8, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'EUR'],
            ['dial_code' => '+49', 'name' => 'Allemagne', 'flag' => '🇩🇪', 'min_digits' => 10, 'max_digits' => 11, 'mobile_prefixes' => null, 'currency_code' => 'EUR'],
            ['dial_code' => '+44', 'name' => 'Royaume-Uni', 'flag' => '🇬🇧', 'min_digits' => 10, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'GBP'],
            ['dial_code' => '+1', 'name' => 'États-Unis', 'flag' => '🇺🇸', 'min_digits' => 10, 'max_digits' => 10, 'mobile_prefixes' => null, 'currency_code' => 'USD'],
            ['dial_code' => '+966', 'name' => 'Arabie Saoudite', 'flag' => '🇸🇦', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'SAR'],
            ['dial_code' => '+971', 'name' => 'Émirats Arabes Unis', 'flag' => '🇦🇪', 'min_digits' => 9, 'max_digits' => 9, 'mobile_prefixes' => null, 'currency_code' => 'AED'],
        ];
    }
}
