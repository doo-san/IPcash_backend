<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Miroir de `InsuranceType` + les durées de `InsuranceDurationScreen`
    // côté Flutter — l'achat y est aujourd'hui câblé sur `Money.zero` en
    // attendant justement ce choix de forfait (voir le commentaire de
    // `insurance_duration_screen.dart`). Comme pour l'eSIM, pas encore
    // branché à l'app (aucun assureur réel intégré non plus — l'achat
    // renvoie toujours `Failure.integrationPending()`, CLAUDE.md règle 9).
    public function up(): void
    {
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('insurance_type'); // auto | moto
            $table->unsignedSmallInteger('duration_months'); // 3 | 6 | 12
            $table->bigInteger('price_xof');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['insurance_type', 'duration_months']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_plans');
    }
};
