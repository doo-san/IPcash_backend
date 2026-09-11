<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `Country` d'openapi.yaml — et de `PhoneCountry` côté
// Flutter (`lib/features/auth/domain/phone_country.dart`).
class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'dialCode' => $this->dial_code,
            'name' => $this->name,
            'flag' => $this->flag,
            'minDigits' => $this->min_digits,
            'maxDigits' => $this->max_digits,
            'mobilePrefixes' => $this->mobile_prefixes,
            'currencyCode' => $this->currency_code,
        ];
    }
}
