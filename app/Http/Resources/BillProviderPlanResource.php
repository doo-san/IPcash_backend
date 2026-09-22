<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `BillProviderPlan` d'openapi.yaml.
class BillProviderPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'kind' => $this->kind,
            'code' => $this->code,
            'label' => $this->label,
            'priceXof' => $this->price_xof,
        ];
    }
}
