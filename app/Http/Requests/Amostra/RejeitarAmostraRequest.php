<?php

namespace App\Http\Requests\Amostra;

use Illuminate\Foundation\Http\FormRequest;

class RejeitarAmostraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'criterioRejeicaoId' => ['required', 'uuid', 'exists:criterios_rejeicao,id'],
            'assinaturaResponsavel' => ['required', 'string'],
            'detalhe' => ['nullable', 'string'],
        ];
    }
}
