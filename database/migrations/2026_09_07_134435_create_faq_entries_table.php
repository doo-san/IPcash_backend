<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Remplace les 6 questions codées en dur dans app_fr.arb/app_en.arb —
    // le support doit pouvoir corriger une réponse (ex. le montant du
    // plafond) sans passer par une release. `locale` séparé plutôt qu'un
    // champ par langue : ajouter une langue ne demande pas de migration.
    public function up(): void
    {
        Schema::create('faq_entries', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_entries');
    }
};
