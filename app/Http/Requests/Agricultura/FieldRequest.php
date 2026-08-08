<?php

namespace App\Http\Requests\Agricultura;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FieldRequest extends FormRequest
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
            'cropId' => ['required', 'uuid', 'exists:crops,id'],
            'fieldCode' => ['nullable', 'string', 'max:50'],
            'areaHa' => ['sometimes', 'numeric', 'min:0'],
            'plantingDate' => ['nullable', 'date'],
            'expectedHarvest' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['planeado', 'plantado', 'em_crescimento', 'colhido', 'abandonado'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
