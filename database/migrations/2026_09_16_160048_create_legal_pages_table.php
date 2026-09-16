<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Page légale simple (titre + contenu riche) éditable depuis
            // l'admin (App\Filament\Resources\LegalPageResource), affichée
            // sur /legal/{slug} (routes/web.php) — remplace les liens
            // "Conditions d'utilisation"/"Confidentialité" du footer qui
            // pointaient vers "#" faute de vraie page.
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        // Seed des deux pages attendues par le footer, avec un texte de
        // repli explicite plutôt qu'une page vide — un vrai contenu
        // juridique doit être rédigé/relu avant le lancement, ceci ne fait
        // que remplacer un lien mort par une page qui dit pourquoi.
        $now = now();
        DB::table('legal_pages')->insert([
            [
                'id' => (string) Str::uuid(),
                'title' => "Conditions d'utilisation",
                'slug' => 'conditions-utilisation',
                'content' => '<p>Le contenu de cette page sera communiqué avant le lancement public d\'IPCash.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Confidentialité',
                'slug' => 'confidentialite',
                'content' => '<p>Le contenu de cette page sera communiqué avant le lancement public d\'IPCash.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
