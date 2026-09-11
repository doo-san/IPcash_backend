<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Répertoire banques + IMF de `bank_link_screen.dart` (22 banques, 7
    // institutions de microfinance) — seule fonctionnalité entièrement
    // absente de l'admin jusqu'ici. La liaison bancaire elle-même reste
    // non intégrée (aucun agrégateur bancaire réel, CLAUDE.md règle 9) :
    // ce référentiel ne fait qu'alimenter la liste affichée à l'utilisateur.
    public function up(): void
    {
        Schema::create('financial_institutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // bank | microfinance
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_institutions');
    }
};
