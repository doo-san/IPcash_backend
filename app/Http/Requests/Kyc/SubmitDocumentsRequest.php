<?php

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDocumentsRequest extends FormRequest
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
            'documentType' => ['required', 'string', 'in:nationalId,passport,driverLicense'],
            'front' => ['required', 'image', 'max:8192'],
            'back' => ['nullable', 'image', 'max:8192'],
            'selfie' => ['required', 'image', 'max:8192'],
            'hasClientSideAnomaly' => ['nullable', 'boolean'],
        ];
    }
}
