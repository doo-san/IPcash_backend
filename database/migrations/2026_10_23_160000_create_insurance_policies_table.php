<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Contrat d'assurance d'un client. Payé depuis le solde à la
    // souscription (`pending`), puis validé (`active`, attestation émise) ou
    // rejeté (`rejected`, remboursé) par un `super_admin`. Le forfait est
    // recopié ici (type, durée, prix) : modifier le catalogue ne doit jamais
    // changer un contrat déjà souscrit.
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // pending | active | rejected

            $table->string('insurance_type'); // auto | moto
            $table->unsignedSmallInteger('duration_months');
            $table->bigInteger('price_xof');
            $table->bigInteger('fee_xof')->default(0);

            $table->string('plate_number');
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_usage'); // personal | professional
            $table->unsignedSmallInteger('horsepower')->nullable();
            $table->unsignedSmallInteger('seats')->nullable();
            $table->string('age_bracket')->nullable();
            $table->unsignedSmallInteger('displacement_cc')->nullable();
            $table->string('body_type')->nullable(); // moto | triporteur

            $table->foreignUuid('purchase_transaction_id')->constrained('transactions');
            $table->foreignUuid('refund_transaction_id')->nullable()->constrained('transactions');

            $table->string('policy_number')->nullable()->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('attestation_path')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
