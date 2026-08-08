<?php

namespace App\Http\Requests\Avaliacao;

use Illuminate\Foundation\Http\FormRequest;

class CriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cycleId' => ['required', 'uuid', 'exists:evaluation_cycles,id'],
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'displayOrder' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
