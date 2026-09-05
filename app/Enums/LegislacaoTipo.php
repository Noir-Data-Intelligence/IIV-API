<?php

namespace App\Enums;

/**
 * Espelha `LEGISLACAO_TIPOS` (`Front-end/src/types/dto/legislacao.ts`) — o
 * frontend já usa um `<Select>` com esta lista fixa; faltava o enum backend
 * correspondente para validar consistentemente (ver auditoria funcional —
 * Legislação/Lei).
 */
enum LegislacaoTipo: string
{
    case Lei = 'Lei';
    case Decreto = 'Decreto';
    case Regulamento = 'Regulamento';
    case Norma = 'Norma';
    case Portaria = 'Portaria';
}
