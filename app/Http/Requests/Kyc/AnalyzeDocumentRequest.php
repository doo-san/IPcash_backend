<?php

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeDocumentRequest extends FormRequest
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
            'typedFirstName' => ['required', 'string', 'max:100'],
            'typedLastName' => ['required', 'string', 'max:100'],
            'front' => ['required', 'image', 'max:8192'],
            'back' => ['nullable', 'image', 'max:8192'],
        ];
    }
}
