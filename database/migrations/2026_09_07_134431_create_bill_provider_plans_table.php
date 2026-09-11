<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Remplace les grilles tarifaires codées en dur dans
    // `bill_payment_form_screen.dart` (`_formulaPriceXof`,
    // `_optionPriceXof`) — aujourd'hui uniquement Canal+ (formules
    // access/évasion/accessPlus/toutCanalPlus + options), extensible à
    // d'autres fournisseurs à tarification variable.
    public function up(): void
    {
        Schema::create('bill_provider_plans', function (Blueprint $table) {
            $table->id();
            $table->string('bill_provider_type');
            $table->string('kind'); // formula | option
            $table->string('code'); // ex. access, evasion, cineSeries…
            $table->string('label');
            $table->bigInteger('price_xof');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['bill_provider_type', 'kind', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_provider_plans');
    }
};
