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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Réglages globaux du site (liens réseaux sociaux, liens
            // App Store/Google Play, images) — voir config/site_settings.php
            // pour la liste des clés et app/Support/helpers.php pour la
            // lecture (site_setting()/site_setting_image_url()). Une ligne
            // n'existe que si un admin l'a renseignée ; `value` contient soit
            // une URL brute (liens), soit un chemin sur le disque `public`
            // (images, via Filament FileUpload).
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
