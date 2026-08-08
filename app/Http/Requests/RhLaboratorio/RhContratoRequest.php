<?php

namespace App\Http\Requests\RhLaboratorio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RhContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employeeId' => ['required', 'uuid', 'exists:rh_laboratorio_colaboradores,id'],
            'contractType' => ['required', Rule::in(['efectivo', 'termo_certo', 'termo_incerto', 'prestacao_servicos', 'estagio'])],
            'position' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date'],
            'salary' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'isActive' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
