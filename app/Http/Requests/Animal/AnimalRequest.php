<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stationId' => ['required', 'uuid', 'exists:estacoes,id'],
            'tag' => ['sometimes', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'species' => ['required', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:100'],
            'sex' => ['sometimes', Rule::in(['macho', 'femea'])],
            'birthDate' => ['nullable', 'date'],
            'motherTag' => ['nullable', 'string', 'max:100'],
            'fatherTag' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['activo', 'vendido', 'morto', 'abatido', 'transferido'])],
            'currentWeightKg' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
