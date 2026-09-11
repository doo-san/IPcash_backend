<?php

namespace App\Http\Requests\Cashio;

use Illuminate\Foundation\Http\FormRequest;

class ConvertToXofRequest extends FormRequest
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
        ];
    }
}
