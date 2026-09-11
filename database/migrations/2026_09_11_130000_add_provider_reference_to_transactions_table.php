<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Identifiant côté prestataire externe (ex. l'id `pt-...` d'un payout Wave
// encore `processing`, sans webhook pour le confirmer — voir
// `App\Console\Commands\FinalizeWavePayouts`) — distinct de notre propre
// `reference`, qui reste l'identifiant IPCash.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('provider_reference')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('provider_reference');
        });
    }
};
