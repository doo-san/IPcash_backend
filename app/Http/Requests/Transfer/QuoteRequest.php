<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    // Plus petit montant qu'un transfert P2P peut porter (voir aussi
    // `transferMinAmount` côté app, et `minimum` dans openapi.yaml).
    public const MIN_AMOUNT_XOF = 5;

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
            'amountXof' => ['required', 'integer', 'min:'.self::MIN_AMOUNT_XOF],
        ];
    }
}
