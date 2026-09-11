<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Montants rapides suggérés — `transfer_amount_screen.dart` en a une
    // liste fixe (1 000/5 000/10 000 F) ; côté cashio ils sont dérivés des
    // bornes de l'opérateur (déjà admin-gérées) donc seul le transfert a
    // vraiment besoin de cette table.
    public function up(): void
    {
        Schema::create('quick_amount_presets', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // transfer | cashIn | cashOut
            $table->bigInteger('amount_xof');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_amount_presets');
    }
};
