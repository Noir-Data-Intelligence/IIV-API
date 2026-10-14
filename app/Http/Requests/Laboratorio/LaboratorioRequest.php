<?php

namespace App\Http\Requests\Laboratorio;

use App\Enums\LaboratorioType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaboratorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // `code` deixou de ser aceite do cliente — gerado pelo servidor
            // em `LaboratorioService::gerarCodigo()` (ver auditoria
            // funcional — geração automática de identificadores).
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_column(LaboratorioType::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'validadorCount' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'slaHoras' => ['nullable', 'integer', 'min:1'],
            'validadoresNomeados' => ['nullable', 'array'],
            'validadoresNomeados.*' => ['uuid', 'exists:users,id'],
            'isActive' => ['sometimes', 'boolean'],
            'slaRuleType' => ['nullable', Rule::in(['horas_desde_entrada', 'horas_desde_entrada_diferenciado', 'dias_uteis', 'gate_qualidade'])],
            'slaHorasNegativo' => ['nullable', 'integer', 'min:1'],
            'slaDias' => ['nullable', 'integer', 'min:1'],
            'slaActive' => ['sometimes', 'boolean'],
            'slaSeverity' => ['nullable', Rule::in(['warning', 'destructive'])],
            'responsavelQualidadeId' => ['nullable', 'uuid', 'exists:users,id'],
            'exigePagamentoParaEntrega' => ['sometimes', 'boolean'],
        ];
    }
}
