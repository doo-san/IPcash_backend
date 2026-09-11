<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Référentiel géré depuis l'admin (Filament) — pas de suppression dure,
    // `is_active` permet de retirer un opérateur de la liste sans casser
    // l'historique des transactions qui le référencent.
    public function up(): void
    {
        Schema::create('mobile_money_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->bigInteger('min_amount_xof');
            $table->bigInteger('max_amount_xof');
            $table->string('flow');
            $table->string('country_dial_code');
            $table->string('currency_code', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_money_providers');
    }
};
