<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class GenerateEphemeralCardRequest extends FormRequest
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
            'pin' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ];
    }
}
