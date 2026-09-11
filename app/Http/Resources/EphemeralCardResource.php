<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `EphemeralCard` d'openapi.yaml — exception isolée à la
// règle 9 de CLAUDE.md, numéro/CVV volontairement invalides au sens de
// Luhn (voir `CardController::generateEphemeralCard`).
class EphemeralCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'cvv' => $this->cvv,
            'expiryMonth' => $this->expiry_month,
            'expiryYear' => $this->expiry_year,
            'balanceXof' => $this->balance_xof,
        ];
    }
}
