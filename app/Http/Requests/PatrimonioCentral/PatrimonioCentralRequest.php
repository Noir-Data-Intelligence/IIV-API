<?php

namespace App\Http\Requests\PatrimonioCentral;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatrimonioCentralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in([
                'equipamento_laboratorio', 'viatura', 'energia', 'refrigeracao',
                'informatica', 'mobiliario', 'outros',
            ])],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'responsibleUserId' => ['nullable', 'uuid', 'exists:users,id'],
            'acquisitionDate' => ['nullable', 'date'],
            'acquisitionCost' => ['sometimes', 'numeric', 'min:0'],
            'currentValue' => ['nullable', 'numeric', 'min:0'],
            'serialNumber' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['activo', 'em_manutencao', 'avariado', 'abatido', 'reservado'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
