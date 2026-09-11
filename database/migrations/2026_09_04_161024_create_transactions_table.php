<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ledger immuable — toute écriture ici se fait dans la même transaction
    // DB que la mise à jour de `accounts.balance_xof` (ou `foreign_balances`)
    // qu'elle représente. `idempotency_key` unique par compte : rejoue la
    // même clé -> même transaction renvoyée, jamais rejouée (CLAUDE.md règle 2).
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->bigInteger('amount_xof');
            $table->bigInteger('fee_xof')->default(0);
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_phone_number')->nullable();
            $table->string('note')->nullable();
            $table->string('reference')->unique();
            $table->string('idempotency_key')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('foreign_currency_code')->nullable();
            $table->bigInteger('foreign_balance_after_minor_units')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'idempotency_key']);
            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
