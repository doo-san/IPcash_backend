<?php

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFaceRequest extends FormRequest
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
            'selfie' => ['required', 'image', 'max:8192'],
            'documentFront' => ['required', 'image', 'max:8192'],
        ];
    }
}
