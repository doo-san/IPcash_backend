<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Prépare le terrain pour brancher un vrai prestataire (mobile money,
    // crédit télécom, facturation) sans nouvelle migration le jour venu —
    // stocke les identifiants, ne prétend jamais qu'une intégration existe
    // déjà (CLAUDE.md règle 9) : tant qu'aucun code métier ne les utilise
    // réellement, ce ne sont que des champs de configuration en attente.
    // `api_key`/`api_secret` chiffrés au repos (cast `encrypted` sur le
    // modèle).
    public function up(): void
    {
        $columns = function (Blueprint $table) {
            $table->string('api_base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->boolean('is_live')->default(false);
        };

        Schema::table('mobile_money_providers', $columns);
        Schema::table('credit_operators', $columns);
        Schema::table('bill_providers', $columns);
    }

    public function down(): void
    {
        $drop = function (Blueprint $table) {
            $table->dropColumn(['api_base_url', 'api_key', 'api_secret', 'is_live']);
        };

        Schema::table('mobile_money_providers', $drop);
        Schema::table('credit_operators', $drop);
        Schema::table('bill_providers', $drop);
    }
};
