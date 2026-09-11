<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `DisplayCurrency` côté Flutter (actuellement des taux figés
    // codés en dur) — permet à l'admin de les mettre à jour sans nouvelle
    // release mobile, une fois l'app branchée sur GET /exchange-rates (pas
    // encore fait : ce endpoint et le passage de `DisplayCurrency` à une
    // source dynamique restent à construire côté Flutter). Taux indicatifs
    // uniquement — jamais utilisés pour un vrai compte multi-devises (voir
    // CLAUDE.md règle 1, exception `ForeignBalance` mise à part).
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->string('currency_code', 3)->primary();
            $table->string('name');
            $table->string('flag', 8)->nullable();
            $table->decimal('rate_to_xof', 12, 4);
            $table->boolean('is_pegged_to_xof')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
