<?php

namespace App\Models;

use App\Enums\InsurancePolicyStatus;
use App\Enums\InsuranceType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Contrat d'assurance souscrit par un client — voir la migration
// `create_insurance_policies_table`. Le statut, le numéro de police, les
// dates de couverture et l'attestation ne sont jamais assignables en masse :
// seules les actions admin Valider/Rejeter (`InsurancePolicyResource`) les
// posent, par assignation directe.
class InsurancePolicy extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $fillable = [
        'insurance_type',
        'duration_months',
        'price_xof',
        'fee_xof',
        'plate_number',
        'vehicle_brand',
        'vehicle_usage',
        'horsepower',
        'seats',
        'age_bracket',
        'displacement_cc',
        'body_type',
    ];

    protected function casts(): array
    {
        return [
            'status' => InsurancePolicyStatus::class,
            'insurance_type' => InsuranceType::class,
            'duration_months' => 'integer',
            'price_xof' => 'integer',
            'fee_xof' => 'integer',
            'horsepower' => 'integer',
            'seats' => 'integer',
            'displacement_cc' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function purchaseTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'purchase_transaction_id');
    }

    public function refundTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'refund_transaction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
