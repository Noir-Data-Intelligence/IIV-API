<?php

namespace App\Enums;

/**
 * Estado do processo laboratorial (requisição) na CGISC:
 *
 *   registada -> em_triagem -> (rejeitada [terminal] | processo_aberto)
 *   processo_aberto -> em_processamento -> aguarda_emissao -> emitida -> encerrada
 *
 * Do `processo_aberto` em diante o estado é DERIVADO dos BI da requisição
 * (RecepcaoService::sincronizarEstado) — nunca escrito pelo cliente.
 * `aguarda_emissao` pode regressar a `em_processamento` quando a CGISC
 * devolve um BI para correcção.
 */
enum RequisicaoStatus: string
{
    case Registada = 'registada';
    case EmTriagem = 'em_triagem';
    case Rejeitada = 'rejeitada';
    case ProcessoAberto = 'processo_aberto';
    case EmProcessamento = 'em_processamento';
    case AguardaEmissao = 'aguarda_emissao';
    case Emitida = 'emitida';
    case Encerrada = 'encerrada';

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Registada => [self::EmTriagem, self::Rejeitada],
            self::EmTriagem => [self::Rejeitada, self::ProcessoAberto],
            self::ProcessoAberto => [self::EmProcessamento, self::AguardaEmissao, self::Emitida],
            self::EmProcessamento => [self::AguardaEmissao, self::Emitida],
            self::AguardaEmissao => [self::EmProcessamento, self::Emitida],
            self::Emitida => [self::Encerrada],
            self::Rejeitada, self::Encerrada => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    /** Fase de recepção/triagem (antes de abrir processo). */
    public function emRecepcao(): bool
    {
        return in_array($this, [self::Registada, self::EmTriagem], true);
    }
}
