<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Un enregistrement par soumission KYC (l'utilisateur peut resoumettre
    // après un rejet). `accounts.kyc_status` reste la valeur courante
    // dénormalisée que l'app mobile lit ; cette table est l'historique +
    // la file de revue admin. Chemins de fichiers vers un disque chiffré
    // (S3 ou équivalent) — jamais en clair dans les logs (CLAUDE.md règle 4).
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('front_path');
            $table->string('back_path')->nullable();
            $table->string('selfie_path');
            $table->boolean('has_client_side_anomaly')->default(false);
            $table->json('document_analysis')->nullable();
            $table->json('face_verification')->nullable();
            $table->string('status')->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
