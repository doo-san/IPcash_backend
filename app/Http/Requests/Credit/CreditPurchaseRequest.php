<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class CreditPurchaseRequest extends FormRequest
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
            'operatorId' => ['required', 'string'],
            'phoneNumber' => ['required', 'string'],
            'amountXof' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'string'],
        ];
    }
}
