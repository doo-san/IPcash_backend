<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'phoneNumber' => ['required', 'string'],
            'pin' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'deviceId' => ['required', 'string'],
        ];
    }
}
