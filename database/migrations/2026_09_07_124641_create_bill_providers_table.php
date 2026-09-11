<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `BillProviderType` côté Flutter (actuellement un simple
    // enum fixe, sans catalogue serveur) — donne à l'admin la main pour
    // activer/désactiver un fournisseur de factures sans nouvelle release.
    public function up(): void
    {
        Schema::create('bill_providers', function (Blueprint $table) {
            $table->string('type')->primary(); // senelec | woyofal | senEau | canalPlus | rapido
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_providers');
    }
};
