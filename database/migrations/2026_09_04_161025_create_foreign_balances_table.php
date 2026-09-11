<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Sous-comptes devise IPchange (`ForeignBalance`) — exception isolée à
    // la règle 1 de CLAUDE.md : solde réel en devise étrangère, toujours en
    // unité mineure (`amount_minor_units`, jamais un float).
    public function up(): void
    {
        Schema::create('foreign_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('currency_code', 3);
            $table->bigInteger('amount_minor_units')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foreign_balances');
    }
};
