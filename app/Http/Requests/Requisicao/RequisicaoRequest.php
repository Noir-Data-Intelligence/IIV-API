<?php

namespace App\Http\Requests\Requisicao;

use App\Enums\TipoAmostra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @deprecated Endpoint legado (POST /requisicoes). Usar
 * POST /lab/recepcao/requisicoes (RegistarRequisicaoRequest), onde a área
 * deriva dos exames pedidos. `laboratorioId` passa a opcional (só para
 * chamadores antigos); `exames` é aceite mas não obrigatório aqui.
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
            'laboratorioId' => ['nullable', 'uuid', 'exists:laboratorios,id'],
            'tipoSujeito' => ['required', 'string', 'in:animal,humano'],
            'clienteNome' => ['required', 'string', 'max:255'],
            'clienteContacto' => ['nullable', 'string', 'max:255'],
            'veterinarioResponsavel' => ['nullable', 'string', 'max:255'],
            'dadosEpidemiologicos' => ['nullable', 'array'],
            'dadosFacturacao' => ['nullable', 'array'],
            'consentimento' => ['required', 'accepted'],
            'assinaturaCliente' => ['required', 'string'],
            'amostras' => ['required', 'array', 'min:1'],
            'amostras.*.tipoAmostra' => ['required', Rule::in(array_column(TipoAmostra::cases(), 'value'))],
            'amostras.*.origemMatriz' => ['nullable', 'string', 'max:255'],
            'amostras.*.pontoColheita' => ['nullable', 'string', 'max:255'],
            'amostras.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'amostras.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'amostras.*.colhidaPor' => ['nullable', 'string', 'max:255'],
            'amostras.*.sujeito' => ['nullable', 'array'],
            'origemTipo' => ['nullable', Rule::in(['publica', 'privada', 'singular'])],
            'exames' => ['nullable', 'array'],
            'exames.*.exameId' => ['required', 'uuid', 'exists:exames_catalogo,id'],
            'exames.*.amostraIndices' => ['nullable', 'array'],
            'exames.*.amostraIndices.*' => ['integer', 'min:0'],
        ];
    }
}
