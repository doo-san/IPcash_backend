<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le plafond de 200 000 F promis dans la FAQ (« Déplafonner mon
    // compte ») n'existe nulle part dans le code — ni table, ni logique.
    // Cette table pose enfin ce réglage : un palier par statut KYC, avec
    // les plafonds qu'il implique. Pas encore appliqué par aucun endpoint
    // (aucune vérification de plafond n'existe côté API pour l'instant).
    public function up(): void
    {
        Schema::create('account_tiers', function (Blueprint $table) {
            $table->string('kyc_status')->primary();
            $table->string('label');
            $table->bigInteger('max_balance_xof')->nullable(); // null = illimité
            $table->bigInteger('daily_send_xof')->nullable();
            $table->bigInteger('monthly_send_xof')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_tiers');
    }
};
