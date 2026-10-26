<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Clôture de compte (AccountClosureService). `phone_number` n'est pas
    // modifié : sous SQLite, changer une colonne reconstruit la table et
    // supprimerait le déclencheur `balance_xof >= 0`. À la clôture, le vrai
    // numéro passe dans `closed_phone_number` et `phone_number` reçoit
    // `closed:<id>` (jamais un numéro valide), ce qui libère le numéro.
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->index();
            $table->string('closed_phone_number')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['closed_at']);
            $table->dropIndex(['closed_phone_number']);
            $table->dropColumn(['closed_at', 'closed_phone_number']);
        });
    }
};
