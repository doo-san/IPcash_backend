<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `Pocket` côté Flutter — sous-compte d'épargne. Un
    // `locked_until` non nul bloque les retraits, jamais les dépôts.
    public function up(): void
    {
        Schema::create('pockets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->bigInteger('balance_xof')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pockets');
    }
};
