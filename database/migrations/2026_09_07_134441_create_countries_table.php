<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `PhoneCountry` côté Flutter (30+ pays, indicatif,
    // longueur de numéro, devise) — aujourd'hui figé dans le code, alors
    // que l'ouverture d'un nouveau pays ou la correction d'un plan de
    // numérotation est une décision produit, pas un changement de code.
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->string('dial_code')->primary(); // ex. +221
            $table->string('name');
            $table->string('flag', 8);
            $table->unsignedTinyInteger('min_digits');
            $table->unsignedTinyInteger('max_digits');
            $table->json('mobile_prefixes')->nullable(); // null = non vérifié
            $table->string('currency_code', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
