<?php

namespace App\Http\Requests\Payment;

use App\Enums\BillProviderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayBillRequest extends FormRequest
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
            'provider' => ['required', Rule::in(array_column(BillProviderType::cases(), 'value'))],
            'accountNumber' => ['required', 'string'],
            'amountXof' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'string'],
        ];
    }
}
