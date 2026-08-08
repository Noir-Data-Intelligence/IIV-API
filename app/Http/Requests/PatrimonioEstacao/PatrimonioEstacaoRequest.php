<?php

namespace App\Http\Requests\PatrimonioEstacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatrimonioEstacaoRequest extends FormRequest
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
            'stationId' => ['required', 'uuid', 'exists:estacoes,id'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'responsibleUser' => ['nullable', 'string', 'max:255'],
            'acquisitionDate' => ['nullable', 'date'],
            'acquisitionCost' => ['sometimes', 'numeric', 'min:0'],
            'currentValue' => ['nullable', 'numeric', 'min:0'],
            'serialNumber' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['activo', 'em_manutencao', 'avariado', 'abatido', 'reservado'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
