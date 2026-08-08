<?php

namespace App\Http\Requests\Inseminacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BreederRequest extends FormRequest
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
            'tag' => ['sometimes', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'species' => ['required', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:100'],
            'birthDate' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['activo', 'inactivo', 'baixado'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
