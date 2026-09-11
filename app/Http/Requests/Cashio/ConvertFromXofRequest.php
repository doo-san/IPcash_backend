<?php

namespace App\Http\Requests\Cashio;

use Illuminate\Foundation\Http\FormRequest;

class ConvertFromXofRequest extends FormRequest
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
            'amountXof' => ['required', 'integer', 'min:1'],
        ];
    }
}
