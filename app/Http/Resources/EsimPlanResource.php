<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Nouveau schéma `EsimPlan` (openapi.yaml) — jusqu'ici `EsimPlanScreen`
// calculait ses prix en dur côté client (voir migration `esim_plans`).
class EsimPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'scope' => $this->scope->value,
            'dataGb' => $this->data_gb,
            'validityDays' => $this->validity_days,
            'priceXof' => $this->price_xof,
        ];
    }
}
