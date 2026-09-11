<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `TransactionDto` côté Flutter (`transaction_dto.dart`) :
    // les champs poche/carte ne sont renseignés que pour les transactions
    // qui en découlent, et toujours en instantané (valeur au moment de
    // l'opération), jamais recalculé depuis l'état actuel de la poche/carte
    // — l'historique d'un client ne doit pas changer de sens si la poche
    // est renommée ou la carte supprimée ensuite. `pocket_id`/`card_id`
    // servent uniquement à filtrer (`GET /pockets/{id}/transactions`,
    // `GET /cards/{id}/transactions`) ; `nullOnDelete` conserve l'écriture
    // même si la poche/carte est supprimée derrière.
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('pocket_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
            $table->foreignUuid('card_id')->nullable()->after('pocket_id')->constrained()->nullOnDelete();
            $table->bigInteger('main_balance_after_xof')->nullable();
            $table->bigInteger('pocket_balance_after_xof')->nullable();
            $table->string('pocket_name')->nullable();
            $table->bigInteger('card_balance_after_xof')->nullable();
            $table->string('card_last4', 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pocket_id');
            $table->dropConstrainedForeignId('card_id');
            $table->dropColumn(['main_balance_after_xof', 'pocket_balance_after_xof', 'pocket_name', 'card_balance_after_xof', 'card_last4']);
        });
    }
};
