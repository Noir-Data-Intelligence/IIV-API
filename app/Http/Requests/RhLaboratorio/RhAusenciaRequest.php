<?php

namespace App\Http\Requests\RhLaboratorio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RhAusenciaRequest extends FormRequest
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
            'leaveType' => ['required', Rule::in(['ferias', 'doenca', 'maternidade', 'paternidade', 'luto', 'sem_vencimento', 'outro'])],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'days' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['pendente', 'aprovada', 'rejeitada', 'concluida'])],
            'reason' => ['required', 'string'],
        ];
    }
}
