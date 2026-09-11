<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPhoneRequest extends FormRequest
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
            // E.164 générique — le contrat openapi.yaml restreint à tort au
            // seul +221 (Sénégal) alors que l'app Flutter (`PhoneCountry`)
            // couvre déjà une trentaine de pays. Corrigé ici plutôt que
            // reproduit, même logique que la note de CLAUDE.md sur les
            // divergences DTO/backend à corriger dans le mapping.
            'phoneNumber' => ['required', 'string', 'regex:/^\+[1-9][0-9]{6,14}$/'],
            'locale' => ['required', 'string', 'in:fr,en'],
        ];
    }
}
