<?php

namespace App\Http\Requests\Processo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `status` fica deliberadamente fora das regras: avançar/devolver/cancelar
     * são as únicas transições válidas (ver ProcessWorkflowService) — um
     * salto arbitrário via PUT bypassava a máquina de estados do workflow
     * (CT-004, QA Gestão de Processos, Não executado). Qualquer `status` que
     * o cliente envie aqui é ignorado por `$request->validated()`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', Rule::in(['baixa', 'normal', 'alta', 'urgente'])],
            'dueDate' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
