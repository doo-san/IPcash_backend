<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir exact du schéma `Account` d'openapi.yaml — camelCase, jamais les
// colonnes snake_case brutes (et jamais `pin_hash`, déjà `$hidden` côté
// modèle mais on ne s'appuie pas sur ça seul ici).
class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phoneNumber' => $this->phone_number,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'kycStatus' => $this->kyc_status->value,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
