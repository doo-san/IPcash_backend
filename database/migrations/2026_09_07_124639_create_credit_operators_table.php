<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `CreditOperator` côté Flutter (achat de crédit télécom).
    // `is_available` = listé pour information mais pas encore proposé
    // (accord commercial manquant), distinct d'un simple `is_active`
    // masquant complètement l'opérateur.
    public function up(): void
    {
        Schema::create('credit_operators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_operators');
    }
};
