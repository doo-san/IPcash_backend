<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Corrige un vrai bug : `PaymentCardDto.balanceXof` est obligatoire
    // côté Flutter (voir payment_card_dto.dart) mais `cards` n'avait
    // aucune colonne pour ça — une vraie carte prépayée aurait fait
    // échouer le parsing JSON. Défauts d'émission (`daily_limit_xof`
    // 200 000 / `monthly_limit_xof` 1 500 000, alignés sur
    // `card_fake_data_source.dart`) déjà appliqués au niveau modèle plutôt
    // qu'ici : la colonne reste nullable, seule sa valeur par défaut change.
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->bigInteger('balance_xof')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('balance_xof');
        });
    }
};
