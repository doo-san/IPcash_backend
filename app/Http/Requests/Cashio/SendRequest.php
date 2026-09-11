<?php

namespace App\Http\Requests\Cashio;

use Illuminate\Foundation\Http\FormRequest;

class SendRequest extends FormRequest
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
            'currencyCode' => ['required', 'string', 'size:3'],
            'amountMinorUnits' => ['required', 'integer', 'min:1'],
            'recipientPhoneNumber' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ];
    }
}
