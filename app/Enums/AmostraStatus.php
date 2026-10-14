<?php

namespace App\Enums;

/**
 * Ciclo de vida da amostra física (DEF-001):
 *
 *   recebida -> em_conferencia -> (aceite | aceite_com_reserva | rejeitada)
 *   aceite|aceite_com_reserva -> em_processamento -> concluida
 *
 * A rejeição (Termo de Rejeição, 2 assinaturas) também pode ocorrer logo a
 * partir de `recebida`.
 */
enum AmostraStatus: string
{
    case Recebida = 'recebida';
    case EmConferencia = 'em_conferencia';
    case Aceite = 'aceite';
    case AceiteComReserva = 'aceite_com_reserva';
    case Rejeitada = 'rejeitada';
    case EmProcessamento = 'em_processamento';
    case Concluida = 'concluida';

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Recebida => [self::EmConferencia, self::Rejeitada],
            self::EmConferencia => [self::Aceite, self::AceiteComReserva, self::Rejeitada],
            self::Aceite, self::AceiteComReserva => [self::EmProcessamento, self::Concluida],
            self::EmProcessamento => [self::Concluida],
            self::Rejeitada, self::Concluida => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    /** Amostra aceite na triagem (com ou sem reserva) — segue para as áreas. */
    public function foiAceite(): bool
    {
        return in_array($this, [self::Aceite, self::AceiteComReserva, self::EmProcessamento, self::Concluida], true);
    }

    /** Triagem concluída (aceite, aceite com reserva ou rejeitada). */
    public function triagemConcluida(): bool
    {
        return $this === self::Rejeitada || $this->foiAceite();
    }
}
