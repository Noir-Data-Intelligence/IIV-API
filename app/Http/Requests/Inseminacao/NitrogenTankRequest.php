<?php

namespace App\Http\Requests\Inseminacao;

use Illuminate\Foundation\Http\FormRequest;

class NitrogenTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'centerId' => ['required', 'uuid', 'exists:ia_centers,id'],
            'code' => ['sometimes', 'string', 'max:100'],
            'capacityL' => ['sometimes', 'numeric', 'min:0'],
            'currentLevelL' => ['sometimes', 'numeric', 'min:0'],
            'minLevelL' => ['sometimes', 'numeric', 'min:0'],
            'lastRefillDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
