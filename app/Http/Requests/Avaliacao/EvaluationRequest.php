<?php

namespace App\Http\Requests\Avaliacao;

use Illuminate\Foundation\Http\FormRequest;

class EvaluationRequest extends FormRequest
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
            'employeeId' => ['required', 'uuid'],
            'evaluatorId' => ['nullable', 'uuid', 'exists:users,id'],
            'evaluationDate' => ['nullable', 'date'],
            'globalScore' => ['nullable', 'numeric', 'min:0'],
            'strengths' => ['nullable', 'string'],
            'improvements' => ['nullable', 'string'],
            'generalComments' => ['nullable', 'string'],
        ];
    }
}
