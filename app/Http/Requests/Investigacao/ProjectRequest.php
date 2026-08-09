<?php

namespace App\Http\Requests\Investigacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lineId' => ['nullable', 'uuid', 'exists:research_lines,id'],
            'title' => ['required', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['proposto', 'aprovado', 'em_curso', 'concluido', 'cancelado'])],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'fundingSource' => ['nullable', 'string', 'max:255'],
            'fundingAmount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'partners' => ['nullable', 'string'],
            'createdBy' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
