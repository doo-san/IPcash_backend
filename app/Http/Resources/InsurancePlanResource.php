<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Nouveau schéma `InsurancePlan` (openapi.yaml) — jusqu'ici
// `InsuranceDurationScreen` était câblé sur `Money.zero` en attendant ce
// choix de forfait (voir migration `insurance_plans`).
class InsurancePlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'insuranceType' => $this->insurance_type->value,
            'durationMonths' => $this->duration_months,
            'priceXof' => $this->price_xof,
        ];
    }
}
