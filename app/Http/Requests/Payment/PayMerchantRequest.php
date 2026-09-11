<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayMerchantRequest extends FormRequest
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
            'merchantId' => ['required', 'string'],
            'operator' => ['required', Rule::in(['orangeMoney', 'wave'])],
            'amountXof' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'string'],
        ];
    }
}
