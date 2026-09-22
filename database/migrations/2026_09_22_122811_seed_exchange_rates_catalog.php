<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Amorce le catalogue de taux avec les mêmes devises et taux que
// `DisplayCurrency` côté Flutter (lib/core/money/display_currency.dart) —
// jusqu'ici, `exchange_rates` n'avait aucun seeder : une base fraîche
// démarrait avec un catalogue vide et GET /exchange-rates (voir
// ForeignBalanceController::exchangeRates()) ne renvoyait rien tant qu'un
// admin n'avait pas tout ressaisi à la main. Ces valeurs restent
// modifiables depuis l'admin (ExchangeRateResource) comme avant — ceci ne
// fait que poser un point de départ cohérent avec ce que l'app affichait
// déjà.
return new class extends Migration
{
    private const RATES = [
        ['currency_code' => 'XOF', 'name' => 'Franc CFA (UEMOA)', 'flag' => '🇸🇳', 'rate_to_xof' => 1, 'is_pegged_to_xof' => true],
        ['currency_code' => 'XAF', 'name' => 'Franc CFA (CEMAC)', 'flag' => '🇨🇲', 'rate_to_xof' => 1, 'is_pegged_to_xof' => true],
        ['currency_code' => 'EUR', 'name' => 'Euro', 'flag' => '🇪🇺', 'rate_to_xof' => 655.957, 'is_pegged_to_xof' => false],
        ['currency_code' => 'USD', 'name' => 'Dollar américain', 'flag' => '🇺🇸', 'rate_to_xof' => 610, 'is_pegged_to_xof' => false],
        ['currency_code' => 'GBP', 'name' => 'Livre sterling', 'flag' => '🇬🇧', 'rate_to_xof' => 780, 'is_pegged_to_xof' => false],
        ['currency_code' => 'CAD', 'name' => 'Dollar canadien', 'flag' => '🇨🇦', 'rate_to_xof' => 445, 'is_pegged_to_xof' => false],
        ['currency_code' => 'CNY', 'name' => 'Yuan chinois', 'flag' => '🇨🇳', 'rate_to_xof' => 85, 'is_pegged_to_xof' => false],
        ['currency_code' => 'MAD', 'name' => 'Dirham marocain', 'flag' => '🇲🇦', 'rate_to_xof' => 62, 'is_pegged_to_xof' => false],
        ['currency_code' => 'GNF', 'name' => 'Franc guinéen', 'flag' => '🇬🇳', 'rate_to_xof' => 0.07, 'is_pegged_to_xof' => false],
        ['currency_code' => 'MRU', 'name' => 'Ouguiya mauritanien', 'flag' => '🇲🇷', 'rate_to_xof' => 16, 'is_pegged_to_xof' => false],
        ['currency_code' => 'GMD', 'name' => 'Dalasi gambien', 'flag' => '🇬🇲', 'rate_to_xof' => 8.5, 'is_pegged_to_xof' => false],
        ['currency_code' => 'GHS', 'name' => 'Cedi ghanéen', 'flag' => '🇬🇭', 'rate_to_xof' => 42, 'is_pegged_to_xof' => false],
        ['currency_code' => 'NGN', 'name' => 'Naira nigérian', 'flag' => '🇳🇬', 'rate_to_xof' => 0.4, 'is_pegged_to_xof' => false],
        ['currency_code' => 'DZD', 'name' => 'Dinar algérien', 'flag' => '🇩🇿', 'rate_to_xof' => 4.5, 'is_pegged_to_xof' => false],
        ['currency_code' => 'TND', 'name' => 'Dinar tunisien', 'flag' => '🇹🇳', 'rate_to_xof' => 195, 'is_pegged_to_xof' => false],
        ['currency_code' => 'CDF', 'name' => 'Franc congolais', 'flag' => '🇨🇩', 'rate_to_xof' => 0.22, 'is_pegged_to_xof' => false],
        ['currency_code' => 'SAR', 'name' => 'Riyal saoudien', 'flag' => '🇸🇦', 'rate_to_xof' => 163, 'is_pegged_to_xof' => false],
        ['currency_code' => 'AED', 'name' => 'Dirham des ÉAU', 'flag' => '🇦🇪', 'rate_to_xof' => 166, 'is_pegged_to_xof' => false],
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::RATES as $rate) {
            DB::table('exchange_rates')->updateOrInsert(
                ['currency_code' => $rate['currency_code']],
                [...$rate, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('exchange_rates')
            ->whereIn('currency_code', array_column(self::RATES, 'currency_code'))
            ->delete();
    }
};
