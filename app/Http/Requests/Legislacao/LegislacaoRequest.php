<?php

namespace App\Http\Requests\Legislacao;

use Illuminate\Foundation\Http\FormRequest;

class LegislacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'num' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['required', 'string', 'max:100'],
            'ano' => ['required', 'string', 'max:10'],
            'pdfUrl' => ['nullable', 'string'],
            'published' => ['boolean'],
        ];
    }
}
