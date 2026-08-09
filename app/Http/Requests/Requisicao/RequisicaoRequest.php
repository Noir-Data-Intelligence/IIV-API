<?php

namespace App\Http\Requests\Requisicao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cria a requisição e o lote de amostras associado numa única chamada —
 * espelha o anexo de lote dos FM-SQ-041/046 (uma requisição, N amostras).
 */
class RequisicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'laboratorioId' => ['required', 'uuid', 'exists:laboratorios,id'],
            'tipoSujeito' => ['required', 'string', 'in:animal,humano'],
            'clienteNome' => ['required', 'string', 'max:255'],
            'clienteContacto' => ['nullable', 'string', 'max:255'],
            'veterinarioResponsavel' => ['nullable', 'string', 'max:255'],
            'dadosEpidemiologicos' => ['nullable', 'array'],
            'dadosFacturacao' => ['nullable', 'array'],
            'consentimento' => ['required', 'accepted'],
            'assinaturaCliente' => ['required', 'string'],
            'amostras' => ['required', 'array', 'min:1'],
            'amostras.*.tipoAmostra' => ['required', 'string', 'max:255'],
            'amostras.*.origemMatriz' => ['nullable', 'string', 'max:255'],
            'amostras.*.pontoColheita' => ['nullable', 'string', 'max:255'],
            'amostras.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'amostras.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'amostras.*.colhidaPor' => ['nullable', 'string', 'max:255'],
            'amostras.*.sujeito' => ['nullable', 'array'],
        ];
    }
}
