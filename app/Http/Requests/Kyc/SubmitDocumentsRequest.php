<?php

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Cette requête arrive forcément en `multipart/form-data` (fichiers
    // joints) — un formulaire multipart ne transporte que des chaînes,
    // jamais un vrai booléen JSON. Le client envoie donc littéralement
    // "true"/"false", que la règle `boolean` de Laravel rejette (elle
    // n'accepte que `true`/`false`/`0`/`1`/`'0'`/`'1'`, pas les mots
    // "true"/"false" — a fait échouer silencieusement (422) chaque
    // soumission KYC réelle jusqu'ici).
    protected function prepareForValidation(): void
    {
        if ($this->has('hasClientSideAnomaly')) {
            $this->merge([
                'hasClientSideAnomaly' => filter_var($this->input('hasClientSideAnomaly'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
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
