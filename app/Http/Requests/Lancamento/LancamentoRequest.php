<?php

namespace App\Http\Requests\Lancamento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LancamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'uuid', 'exists:contas,id'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'type' => ['required', Rule::in(['receita', 'despesa'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'transactionDate' => ['required', 'date'],
            // `min:2` replica a validação já existente no formulário
            // (`buildTransactionSchema` em Financeiro.tsx) — antes só a
            // obrigatoriedade básica estava no backend, pelo que uma chamada
            // directa à API conseguia gravar uma descrição com 1 carácter
            // mesmo com a UI a bloquear isso (CT-015, relatório de QA).
            'description' => ['required', 'string', 'min:2'],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['pendente', 'pago', 'cancelado'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
