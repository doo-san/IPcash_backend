<?php

namespace App\Http\Requests\Esim;

use Illuminate\Foundation\Http\FormRequest;

class EsimActivateRequest extends FormRequest
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
            'countryName' => ['required', 'string'],
            'planLabel' => ['required', 'string'],
            'amountXof' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'string'],
        ];
    }
}
