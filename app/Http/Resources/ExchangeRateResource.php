<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `ExchangeRate` d'openapi.yaml — voir
// ForeignBalanceController::exchangeRates(). Même taux que celui utilisé
// pour de vrai par convertFromXof()/convertToXof() (ExchangeRate::rate_to_xof) :
// contrairement à `DisplayCurrency` côté Flutter (taux codés en dur,
// purement indicatifs ailleurs dans l'app), cet endpoint doit rester la
// source de vérité pour tout écran qui débouche sur une vraie conversion.
class ExchangeRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'currencyCode' => $this->currency_code,
            'name' => $this->name,
            'flag' => $this->flag,
            'rateToXof' => (float) $this->rate_to_xof,
            'isPeggedToXof' => $this->is_pegged_to_xof,
        ];
    }
}
