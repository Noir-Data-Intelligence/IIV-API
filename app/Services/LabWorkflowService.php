<?php

namespace App\Services;

use App\Models\Requisicao;
use App\Services\Lab\NumeracaoService;

/**
 * @deprecated Façade fino mantido para chamadores antigos. O workflow do
 * Laboratório foi dividido por fase (redesenho DEF-001/002/003):
 *
 *  - App\Services\Lab\RecepcaoService      (CGISC: registo, dupla conferência, rejeição, abrir processo/PAT)
 *  - App\Services\Lab\EncaminhamentoService (encaminhar, receber na área, reencaminhar)
 *  - App\Services\Lab\AreaWorkflowService  (ensaios, validação multi-interveniente, devolução)
 *  - App\Services\Lab\EmissaoService       (recepção da devolução, emissão do BA, entrega, encerramento)
 *
 * Os antigos aceitarAmostra()/registarResultado()/validar()/comunicar()
 * foram removidos: implementavam o fluxo num só clique (o próprio defeito).
 */
class LabWorkflowService
{
    public function __construct(private readonly NumeracaoService $numeracao) {}

    /**
     * PAT `PAT-###-ANO` — agora a partir da série única `sequencias` com
     * lock (sem duplicados nem colisões). Os parâmetros antigos (tabela,
     * coluna) são ignorados: há uma única série de PAT.
     */
    public function gerarNumeroPat(?string $table = null, ?string $column = null): string
    {
        return $this->numeracao->pat();
    }

    public function gerarNumeroRequisicao(): string
    {
        return $this->numeracao->requisicao();
    }

    public function gerarNumeroAmostra(Requisicao $requisicao, int $indice): string
    {
        return "{$requisicao->numero}-A{$indice}";
    }
}
