<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `Card` d'openapi.yaml — jamais de PAN/CVV complet, on
// ne les stocke nulle part (voir la migration `cards`).
class CardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'last4' => $this->last4,
            'status' => $this->status->value,
            'expiryMonth' => $this->expiry_month,
            'expiryYear' => $this->expiry_year,
            'dailyLimitXof' => $this->daily_limit_xof,
            'monthlyLimitXof' => $this->monthly_limit_xof,
            'balanceXof' => $this->balance_xof,
        ];
    }
}
