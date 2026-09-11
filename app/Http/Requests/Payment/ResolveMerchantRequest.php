<?php

namespace App\Http\Requests\Payment;

use App\Enums\MerchantIdentifierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveMerchantRequest extends FormRequest
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
            'identifierType' => ['required', Rule::in(array_column(MerchantIdentifierType::cases(), 'value'))],
            'identifierValue' => ['required', 'string'],
        ];
    }
}
