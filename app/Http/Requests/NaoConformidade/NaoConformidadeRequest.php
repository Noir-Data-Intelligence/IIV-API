<?php

namespace App\Http\Requests\NaoConformidade;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NaoConformidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'severity' => ['required', Rule::in(['menor', 'maior', 'critica'])],
            'status' => [Rule::in(['aberta', 'em_resolucao', 'resolvida', 'encerrada'])],
            'correctiveAction' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'auditId' => ['nullable', 'uuid', 'exists:auditorias,id'],
        ];
    }
}
