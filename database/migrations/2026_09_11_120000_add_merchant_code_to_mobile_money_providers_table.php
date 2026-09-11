<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Requis par certains prestataires (ex. OM Pay d'Orange/Sonatel, voir
// OrangeMoneyClient) en plus de `api_key`/`api_secret` — un identifiant
// marchand transmis en clair dans le corps de chaque requête, pas un
// secret à masquer.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_money_providers', function (Blueprint $table) {
            $table->string('merchant_code')->nullable()->after('api_secret');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_money_providers', function (Blueprint $table) {
            $table->dropColumn('merchant_code');
        });
    }
};
