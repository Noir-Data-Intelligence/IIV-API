<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MissaoRequest extends FormRequest
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
            'destination' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'status' => ['sometimes', Rule::in(['planeada', 'submetida', 'aprovada', 'em_curso', 'concluida', 'cancelada'])],
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
