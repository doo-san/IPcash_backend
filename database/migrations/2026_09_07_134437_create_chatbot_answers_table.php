<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Remplace la base de connaissances mot-clé → réponse codée en dur
    // dans `support_chat_fake_data_source.dart` — chaque ligne est
    // déclenchée quand un des mots-clés apparaît dans le message du
    // client. `is_fallback` marque la réponse par défaut (une seule
    // devrait l'être, appliqué au niveau de l'admin, pas en contrainte
    // SQL portable SQLite/MySQL).
    public function up(): void
    {
        Schema::create('chatbot_answers', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->json('keywords'); // ex. ["carte", "carte prépayée"]
            $table->text('answer');
            $table->boolean('is_fallback')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_answers');
    }
};
