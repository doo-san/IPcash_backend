<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Exception explicite et isolée à la règle 9 de CLAUDE.md : numéro/CVV
    // générés côté serveur, volontairement invalides au sens de Luhn. Une
    // seule carte active à la fois par compte (`destroyed_at` null) —
    // contrainte applicative, pas d'index partiel ici pour rester portable
    // entre SQLite (dev) et MySQL (prod). `number`/`cvv` chiffrés au repos
    // via le cast `encrypted` du modèle.
    public function up(): void
    {
        Schema::create('ephemeral_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->text('number');
            $table->text('cvv');
            $table->unsignedTinyInteger('expiry_month');
            $table->unsignedSmallInteger('expiry_year');
            $table->bigInteger('balance_xof')->default(0);
            $table->timestamp('destroyed_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'destroyed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ephemeral_cards');
    }
};
