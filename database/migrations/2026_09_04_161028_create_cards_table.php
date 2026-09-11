<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le PAN/CVV complets ne transitent JAMAIS par cette table ni par
    // aucun endpoint (voir /cards/{id}/reveal dans openapi.yaml) : tant
    // qu'aucun prestataire d'émission (PCI-DSS) n'est branché, seul le
    // `last4` est connu du backend. `external_processor_id` accueillera la
    // référence carte du futur prestataire une fois intégré.
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('last4', 4);
            $table->string('status')->default('active');
            $table->unsignedTinyInteger('expiry_month');
            $table->unsignedSmallInteger('expiry_year');
            $table->bigInteger('daily_limit_xof')->nullable();
            $table->bigInteger('monthly_limit_xof')->nullable();
            $table->string('external_processor_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
