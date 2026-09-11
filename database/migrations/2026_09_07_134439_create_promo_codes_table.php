<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le champ `promoCode` existe déjà côté Flutter
    // (`cashio_switch_flow_controller.dart`) mais ne mène nulle part —
    // aucune validation, aucune remise. Cette table donne enfin un
    // catalogue réel derrière ce champ ; aucun code métier ne l'applique
    // encore (pas de endpoint dédié dans openapi.yaml).
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('discount_type'); // percent | fixed
            $table->unsignedInteger('discount_value'); // points de base ou XOF
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemptions_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
