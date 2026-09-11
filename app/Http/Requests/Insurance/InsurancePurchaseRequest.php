<?php

namespace App\Http\Requests\Insurance;

use App\Enums\InsuranceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsurancePurchaseRequest extends FormRequest
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
            'type' => ['required', Rule::in(array_column(InsuranceType::cases(), 'value'))],
            'plateNumber' => ['required', 'string'],
            'amountXof' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'string'],
        ];
    }
}
