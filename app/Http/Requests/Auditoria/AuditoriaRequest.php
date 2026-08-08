<?php

namespace App\Http\Requests\Auditoria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditoriaRequest extends FormRequest
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
            'auditType' => ['required', Rule::in(['interna', 'externa', 'ISO'])],
            'auditor' => ['required', 'string', 'max:255'],
            'scheduledDate' => ['required', 'date'],
            'completedDate' => ['nullable', 'date'],
            'status' => [Rule::in(['planeada', 'em_curso', 'concluida', 'cancelada'])],
            'findings' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'laboratoryId' => ['nullable', 'uuid', 'exists:laboratorios,id'],
        ];
    }
}
