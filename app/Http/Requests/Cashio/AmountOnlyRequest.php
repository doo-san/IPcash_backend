<?php

namespace App\Http\Requests\Cashio;

use Illuminate\Foundation\Http\FormRequest;

// Réutilisée par /cashin/card, /cashin/bank, /cashin/paypal et
// /cards/ephemeral-card/{recharge,transfer-out} — même forme minimale
// (montant seul, voir openapi.yaml).
class AmountOnlyRequest extends FormRequest
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
            'amountXof' => ['required', 'integer', 'min:1'],
        ];
    }
}
