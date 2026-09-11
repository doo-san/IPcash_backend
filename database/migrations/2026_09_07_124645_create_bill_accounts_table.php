<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `BillAccount` côté Flutter — compte de facturation
    // enregistré (compteur Woyofal, numéro de réabonnement Canal+…).
    // `bill_provider_type` ajouté par rapport à l'entité Flutter : sans lui
    // la table ne saurait pas à quel fournisseur chaque compte se rapporte.
    public function up(): void
    {
        Schema::create('bill_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('bill_provider_type');
            $table->string('nickname');
            $table->string('account_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_accounts');
    }
};
