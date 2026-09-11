<?php

namespace App\Http\Requests\Laboratorio;

use Illuminate\Foundation\Http\FormRequest;

class LaboratorioTecnicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'userId' => ['required', 'uuid', 'exists:users,id'],
            'funcao' => ['nullable', 'string', 'max:100'],
        ];
    }
}
