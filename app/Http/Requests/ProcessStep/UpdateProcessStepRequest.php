<?php

namespace App\Http\Requests\ProcessStep;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcessStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['pendente', 'em_curso', 'concluida', 'rejeitada', 'devolvida'])],
            'notes' => ['sometimes', 'nullable', 'string'],
            'startedAt' => ['sometimes', 'nullable', 'date'],
            'completedAt' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
