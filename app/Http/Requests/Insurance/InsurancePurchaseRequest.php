<?php

namespace App\Http\Requests\Insurance;

use App\Enums\InsuranceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Aucun montant n'est accepté du client : le prix vient toujours du forfait
// actif (type × durée) lu côté serveur, voir `InsuranceController::purchase`.
class InsurancePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('plateNumber'))) {
            $this->merge([
                'plateNumber' => strtoupper(preg_replace('/\s+/', '', $this->input('plateNumber'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_column(InsuranceType::cases(), 'value'))],
            'durationMonths' => ['required', 'integer'],
            'plateNumber' => ['required', 'string', 'regex:/^[A-Z0-9-]{4,15}$/'],
            'vehicleBrand' => ['nullable', 'string', 'max:50'],
            'vehicleUsage' => ['required', Rule::in(['personal', 'professional'])],
            'horsepower' => ['required_if:type,auto', 'nullable', 'integer', 'between:1,999'],
            'seats' => ['required_if:type,auto', 'nullable', 'integer', 'between:1,99'],
            'ageBracket' => ['required_if:type,auto', 'nullable', Rule::in(['lessThan3', 'threeToFive', 'fiveToTen', 'moreThanTen'])],
            'displacementCc' => ['required_if:type,moto', 'nullable', 'integer', 'between:1,5000'],
            'bodyType' => ['required_if:type,moto', 'nullable', Rule::in(['moto', 'triporteur'])],
            'pin' => ['required', 'string'],
        ];
    }
}
