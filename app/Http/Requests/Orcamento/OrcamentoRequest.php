<?php

namespace App\Http\Requests\Orcamento;

use Illuminate\Foundation\Http\FormRequest;

class OrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'accountId' => ['required', 'uuid', 'exists:contas,id'],
            'departmentId' => ['nullable', 'uuid', 'exists:departments,id'],
            'plannedAmount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
