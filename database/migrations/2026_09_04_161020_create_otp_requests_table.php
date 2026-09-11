<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Couvre /auth/register/phone -> /auth/otp/verify -> /auth/pin. Le
    // `session_token` n'est renseigné qu'une fois l'OTP validé et sert de
    // jeton temporaire (`X-Session-Token`) pour la seule création du PIN.
    public function up(): void
    {
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number');
            $table->string('code_hash');
            $table->string('locale', 5)->default('fr');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('session_token')->nullable()->unique();
            $table->timestamp('session_token_expires_at')->nullable();
            $table->timestamps();

            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
