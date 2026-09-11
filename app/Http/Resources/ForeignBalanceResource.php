<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `ForeignBalance` d'openapi.yaml.
class ForeignBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'currencyCode' => $this->currency_code,
            'amountMinorUnits' => $this->amount_minor_units,
        ];
    }
}
