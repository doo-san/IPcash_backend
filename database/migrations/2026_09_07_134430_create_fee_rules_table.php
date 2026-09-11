<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Remplace le taux de transfert P2P codé en dur (0,5 %, voir
    // `transfer_fake_data_source.dart:_feeRateBasisPoints`) — pas encore
    // branché à l'app (aucun endpoint de frais dans openapi.yaml), mais
    // donne à l'admin la main sur le paramètre le plus sensible du produit
    // sans attendre une release mobile.
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->unique(); // p2pTransfer | cashIn | cashOut | billPayment | cardTopUp | foreignExchange | insurance
            $table->string('type'); // percent | fixed
            $table->unsignedInteger('value'); // points de base (1/100 %) si percent, XOF si fixed
            $table->bigInteger('min_fee_xof')->nullable();
            $table->bigInteger('max_fee_xof')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
