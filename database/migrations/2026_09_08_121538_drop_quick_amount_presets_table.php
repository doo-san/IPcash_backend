<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // `QuickAmountPresetResource` proposait des montants suggérés
    // configurables (transfert/dépôt/retrait) mais rien côté API/app ne
    // les a jamais consommés — `transfer_amount_screen.dart` garde sa
    // liste codée en dur (1 000/5 000/10 000 F). Demande produit : retirer
    // ce réglage plutôt que de le garder inutilisé (même décision que
    // pour les paliers de compte).
    public function up(): void
    {
        Schema::dropIfExists('quick_amount_presets');
    }

    public function down(): void
    {
        Schema::create('quick_amount_presets', function (Blueprint $table) {
            $table->id();
            $table->string('scope');
            $table->bigInteger('amount_xof');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }
};
