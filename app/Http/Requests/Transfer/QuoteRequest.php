<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
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
            'recipientPhoneNumber' => ['required', 'string'],
            'amountXof' => ['required', 'integer', 'min:1'],
        ];
    }
}
