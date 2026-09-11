<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `CreditOperator` d'openapi.yaml. `isAvailable` reflète
// `is_available` (jamais `isConfigured()`/`is_live` — un opérateur non
// configuré reste visible mais l'achat renverra INTEGRATION_PENDING).
class CreditOperatorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logoUrl' => $this->logoUrl(),
            'isAvailable' => $this->is_available,
        ];
    }
}
