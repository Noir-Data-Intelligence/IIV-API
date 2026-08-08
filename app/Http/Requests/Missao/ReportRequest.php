<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reportDate' => ['required', 'date'],
            'summary' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['rascunho', 'submetido', 'aprovado', 'rejeitado'])],
            'submittedBy' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
