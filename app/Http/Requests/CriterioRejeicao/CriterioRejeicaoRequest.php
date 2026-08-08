<?php

namespace App\Http\Requests\CriterioRejeicao;

use Illuminate\Foundation\Http\FormRequest;

class CriterioRejeicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'laboratorioId' => ['nullable', 'uuid', 'exists:laboratorios,id'],
            'codigo' => ['required', 'string', 'max:50'],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }
}
