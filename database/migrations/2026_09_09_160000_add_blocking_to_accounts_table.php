<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Blocage d'un client par l'admin — distinct de `locked_until`
    // (verrouillage automatique après trop d'échecs de code secret, que le
    // client lève lui-même en le retapant plus tard) et du statut KYC
    // `suspended` (restreint seulement les mouvements d'argent côté app).
    // Un blocage admin coupe tout accès à l'API, connexion comprise (voir
    // `Account::isBlocked()`, `EnsureAccountIsNotBlocked`).
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable(); // blocage définitif
            $table->timestamp('blocked_until')->nullable(); // blocage temporaire
            $table->string('block_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['blocked_at', 'blocked_until', 'block_reason']);
        });
    }
};
