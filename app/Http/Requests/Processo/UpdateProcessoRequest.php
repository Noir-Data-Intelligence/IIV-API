<?php

namespace App\Http\Requests\Processo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', Rule::in(['baixa', 'normal', 'alta', 'urgente'])],
            'dueDate' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['aberto', 'em_curso', 'concluido', 'cancelado'])],
        ];
    }
}
