<?php

namespace App\Http\Requests\Cashio;

use Illuminate\Foundation\Http\FormRequest;

class CashoutRequest extends FormRequest
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
            'amountXof' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            // Voir CashinRequest::rules — même champ, même justification.
            'promoCode' => ['nullable', 'string'],
        ];
    }
}
