<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'fullName' => ['sometimes', 'string', 'max:200'],
            'email' => ['sometimes', 'nullable', 'email'],
            'notificationsEnabled' => ['sometimes', 'boolean'],
            'preferredLocale' => ['sometimes', 'string', 'in:fr,en'],
        ];
    }
}
