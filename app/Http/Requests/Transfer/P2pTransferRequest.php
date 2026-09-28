<?php

namespace App\Http\Requests\Transfer;

use App\Support\AppConfig;
use Illuminate\Foundation\Http\FormRequest;

class P2pTransferRequest extends FormRequest
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
            'amountXof' => ['required', 'integer', 'min:'.AppConfig::int('transfer_min_amount_xof')],
            'note' => ['nullable', 'string', 'max:140'],
            'pin' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ];
    }
}
