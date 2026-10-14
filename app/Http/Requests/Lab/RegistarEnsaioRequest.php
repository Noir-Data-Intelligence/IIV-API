<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Boletim de ensaio (interno à área) de uma amostra × exame. */
class RegistarEnsaioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amostraId' => ['required', 'uuid'],
            'exameId' => ['required', 'uuid'],
            'tecnica' => ['nullable', 'string', 'max:255'],
            'popCodigo' => ['nullable', 'string', 'max:100'],
            'equipamentoId' => ['nullable', 'string', 'max:100'],
            'loteReagente' => ['nullable', 'string', 'max:100'],
            'segundoIntervenienteId' => ['nullable', 'uuid', 'exists:users,id'],
            'visualizadorId' => ['nullable', 'uuid', 'exists:users,id'],
            'resultado' => ['nullable', 'array'],
            'resultadoQualitativo' => ['nullable', Rule::in(['positivo', 'negativo', 'inconclusivo'])],
            'interpretacao' => ['nullable', 'string'],
        ];
    }
}
