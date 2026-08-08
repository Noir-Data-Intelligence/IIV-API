<?php

namespace App\Http\Requests\Inseminacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SemenDoseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'breederId' => ['required', 'uuid', 'exists:breeders,id'],
            'tankId' => ['nullable', 'uuid', 'exists:nitrogen_tanks,id'],
            'collectionDate' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:0'],
            'availableQuantity' => ['nullable', 'integer', 'min:0'],
            'qualityGrade' => ['sometimes', Rule::in(['A', 'B', 'C'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
