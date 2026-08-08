<?php

namespace App\Http\Requests\Formacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormacaoRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'trainer' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date'],
            'hours' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['planeada', 'em_curso', 'concluida', 'cancelada'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
