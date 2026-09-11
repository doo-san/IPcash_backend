<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Jeton à usage unique et courte durée pour POST /cards/{id}/reveal —
    // le PAN/CVV réel n'est renvoyé qu'au prestataire d'émission une fois
    // branché, jamais stocké ici (voir openapi.yaml, section /cards/{id}/reveal).
    public function up(): void
    {
        Schema::create('card_reveal_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_reveal_tokens');
    }
};
