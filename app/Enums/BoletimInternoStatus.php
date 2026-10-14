<?php

namespace App\Enums;

/**
 * Máquina de estados do Boletim Interno (BI) — o único documento que
 * circula fisicamente entre a CGISC (Recepção) e as áreas (DEF-002/003):
 *
 *   gerado -> em_transito -> recebido_area -> em_analise -> resultado_registado
 *   resultado_registado -> em_validacao (n<N) -> validado (n=N + gates)
 *   validado -> devolvido -> recebido_cgisc -> emitido
 *   recebido_cgisc -> em_analise                 (devolver para correcção)
 *   recebido_area|em_analise -> rejeitado_na_area (todas as amostras rejeitadas)
 *
 * O reencaminhamento para outra área não muda o estado deste BI: cria um
 * novo BI na área de destino (ver EncaminhamentoService::reencaminhar).
 *
 * Nunca se escreve `status` directamente: os serviços em App\Services\Lab
 * chamam assertTransition(), que recusa transições inválidas com 422.
 */
enum BoletimInternoStatus: string
{
    case Gerado = 'gerado';
    case EmTransito = 'em_transito';
    case RecebidoArea = 'recebido_area';
    case EmAnalise = 'em_analise';
    case ResultadoRegistado = 'resultado_registado';
    case EmValidacao = 'em_validacao';
    case Validado = 'validado';
    case Devolvido = 'devolvido';
    case RecebidoCgisc = 'recebido_cgisc';
    case Emitido = 'emitido';
    case RejeitadoNaArea = 'rejeitado_na_area';

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Gerado => [self::EmTransito],
            self::EmTransito => [self::RecebidoArea],
            self::RecebidoArea => [self::EmAnalise, self::RejeitadoNaArea],
            self::EmAnalise => [self::ResultadoRegistado, self::RejeitadoNaArea],
            self::ResultadoRegistado => [self::EmValidacao, self::Validado],
            self::EmValidacao => [self::EmValidacao, self::Validado],
            self::Validado => [self::Devolvido],
            self::Devolvido => [self::RecebidoCgisc],
            self::RecebidoCgisc => [self::Emitido, self::EmAnalise],
            self::Emitido, self::RejeitadoNaArea => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /** Estados em que o BI está fisicamente na área (fila "Em análise"/"Em validação"). */
    public function naArea(): bool
    {
        return in_array($this, [self::RecebidoArea, self::EmAnalise, self::ResultadoRegistado, self::EmValidacao, self::Validado], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
