<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // `accounts` = client mobile IPCash (téléphone + PIN), distinct de
    // `users` = staff admin (email + mot de passe, panneau Filament). Ne
    // jamais fusionner les deux : ce sont deux populations et deux modes
    // d'auth complètement différents.
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('pin_hash')->nullable();
            $table->unsignedTinyInteger('failed_pin_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('kyc_status')->default('notStarted');
            $table->string('kyc_rejection_reason')->nullable();
            // Toujours un `int` XOF, jamais de `double` (CLAUDE.md règle 1).
            $table->bigInteger('balance_xof')->default(0);
            $table->string('email')->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->string('preferred_locale', 5)->default('fr');
            $table->boolean('biometric_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
