<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Nouveau schéma `FinancialInstitution` (openapi.yaml) — jusqu'ici
// `BankLinkScreen` utilisait une liste statique embarquée côté client.
class FinancialInstitutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'logoUrl' => $this->logoUrl(),
        ];
    }
}
