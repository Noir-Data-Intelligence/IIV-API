<?php

namespace App\Http\Requests\InqueritoSatisfacao;

use Illuminate\Foundation\Http\FormRequest;

class InqueritoSatisfacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'requisicaoId' => ['required', 'uuid', 'exists:requisicoes,id'],
            'dimensoes' => ['required', 'array', 'min:1'],
            'comentario' => ['nullable', 'string'],
        ];
    }
}
