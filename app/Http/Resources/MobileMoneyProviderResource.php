<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `MobileMoneyProvider` d'openapi.yaml — camelCase, ne
// renvoie jamais `api_key`/`api_secret` (déjà `$hidden` côté modèle, mais
// ce Resource explicite le contrat plutôt que de dépendre de ça seul).
class MobileMoneyProviderResource extends JsonResource
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
            'minAmountXof' => $this->min_amount_xof,
            'maxAmountXof' => $this->max_amount_xof,
            'flow' => $this->flow->value,
            'countryDialCode' => $this->country_dial_code,
            'currencyCode' => $this->currency_code,
        ];
    }
}
