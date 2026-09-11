<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `_EsimScope`/`_EsimData`/`_priceXof()` côté Flutter
    // (`esim_plan_screen.dart`), actuellement des prix calculés en dur
    // (base × multiplicateur). Ici l'admin fixe directement le prix final
    // de chaque combinaison scope × palier de données — pas encore branché
    // à l'app (aucun endpoint `/esim/plans` dans openapi.yaml pour
    // l'instant, l'app calcule toujours ses prix elle-même).
    public function up(): void
    {
        Schema::create('esim_plans', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // local | regional | global
            $table->unsignedInteger('data_gb');
            $table->unsignedSmallInteger('validity_days');
            $table->bigInteger('price_xof');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['scope', 'data_gb']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_plans');
    }
};
