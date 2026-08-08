<?php

namespace App\Http\Requests\Agricultura;

use Illuminate\Foundation\Http\FormRequest;

class HarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fieldId' => ['required', 'uuid', 'exists:fields,id'],
            'harvestDate' => ['nullable', 'date'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'qualityGrade' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
