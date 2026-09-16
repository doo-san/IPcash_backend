<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // `page`+`key` identifient un texte défini dans
            // config('site_content') (ex. page=home, key=hero_subtitle) —
            // une ligne n'existe ici que si un admin l'a modifié ; sinon
            // site_content() retombe sur le défaut du config (voir
            // app/Support/site_content.php).
            $table->string('page');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['page', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
