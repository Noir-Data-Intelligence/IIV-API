<?php

namespace App\Http\Requests\Lab;

use App\Enums\TipoAmostra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registo na Recepção (CGISC): requisição + amostras + exames pedidos. Não
 * há selecção de laboratório — a(s) área(s) derivam dos exames
 * (`exames.*.amostraIndices` null/vazio = o exame aplica-se a todas as
 * amostras).
 */
class RegistarRequisicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipoSujeito' => ['required', 'string', 'in:animal,humano'],
            'clienteNome' => ['required', 'string', 'max:255'],
            'clienteContacto' => ['nullable', 'string', 'max:255'],
            'veterinarioResponsavel' => ['nullable', 'string', 'max:255'],
            'dadosEpidemiologicos' => ['nullable', 'array'],
            'dadosFacturacao' => ['nullable', 'array'],
            'consentimento' => ['required', 'accepted'],
            'assinaturaCliente' => ['required', 'string'],
            'origemTipo' => ['required', Rule::in(['publica', 'privada', 'singular'])],
            'provenienciaLocal' => ['sometimes', 'boolean'],
            'termoEntregaNumero' => ['nullable', 'string', 'max:100'],
            'termoEntregaAssinadoPor' => ['nullable', 'string', 'max:255'],
            'entreguePor' => ['nullable', 'string', 'max:255'],
            'conservacao' => ['nullable', 'string', 'max:255'],
            'colheitaEm' => ['nullable', 'date'],
            'doencasSuspeitas' => ['nullable', 'string'],
            'amostras' => ['required', 'array', 'min:1'],
            'amostras.*.tipoAmostra' => ['required', Rule::in(array_column(TipoAmostra::cases(), 'value'))],
            'amostras.*.origemMatriz' => ['nullable', 'string', 'max:255'],
            'amostras.*.pontoColheita' => ['nullable', 'string', 'max:255'],
            'amostras.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'amostras.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'amostras.*.colhidaPor' => ['nullable', 'string', 'max:255'],
            'amostras.*.sujeito' => ['nullable', 'array'],
            'exames' => ['required', 'array', 'min:1'],
            'exames.*.exameId' => ['required', 'uuid', 'exists:exames_catalogo,id'],
            'exames.*.amostraIndices' => ['nullable', 'array'],
            'exames.*.amostraIndices.*' => ['integer', 'min:0'],
        ];
    }
}
