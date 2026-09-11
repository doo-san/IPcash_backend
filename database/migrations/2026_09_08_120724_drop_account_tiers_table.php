<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // `AccountTierResource` posait un plafond par statut KYC (solde max,
    // envoi/jour, envoi/mois) mais aucun endpoint ne l'a jamais appliqué —
    // demande produit : retirer ce réglage trompeur plutôt que de le
    // brancher, le produit n'a plus de notion d'accès plafonné par palier
    // KYC (voir le reste du parcours KYC : bloqué tant que non vérifié).
    public function up(): void
    {
        Schema::dropIfExists('account_tiers');
    }

    public function down(): void
    {
        Schema::create('account_tiers', function (Blueprint $table) {
            $table->string('kyc_status')->primary();
            $table->string('label');
            $table->bigInteger('max_balance_xof')->nullable();
            $table->bigInteger('daily_send_xof')->nullable();
            $table->bigInteger('monthly_send_xof')->nullable();
            $table->timestamps();
        });
    }
};
