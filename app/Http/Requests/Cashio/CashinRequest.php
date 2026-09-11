<?php

namespace App\Http\Requests\Cashio;

use Illuminate\Foundation\Http\FormRequest;

class CashinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'providerId' => ['required', 'string'],
            'sourcePhoneNumber' => ['required', 'string'],
            'amountXof' => ['required', 'integer', 'min:1'],
            // Champ existant côté app (`CashioSwitchFlowController.promoCode`)
            // mais jusqu'ici jamais transmis à aucun endpoint — voir
            // PromoCode::isValidNow().
            'promoCode' => ['nullable', 'string'],
        ];
    }
}
