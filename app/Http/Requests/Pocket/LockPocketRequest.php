<?php

namespace App\Http\Requests\Pocket;

use Illuminate\Foundation\Http\FormRequest;

class LockPocketRequest extends FormRequest
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
            'until' => ['required', 'date', 'after:now'],
        ];
    }
}
