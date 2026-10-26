<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `InsurancePolicy` d'openapi.yaml.
class InsurancePolicyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'insuranceType' => $this->insurance_type->value,
            'durationMonths' => $this->duration_months,
            'priceXof' => $this->price_xof,
            'feeXof' => $this->fee_xof,
            'plateNumber' => $this->plate_number,
            'vehicleBrand' => $this->vehicle_brand,
            'policyNumber' => $this->policy_number,
            'startsAt' => $this->starts_at?->toIso8601String(),
            'endsAt' => $this->ends_at?->toIso8601String(),
            'rejectionReason' => $this->rejection_reason,
            'hasAttestation' => $this->attestation_path !== null,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
