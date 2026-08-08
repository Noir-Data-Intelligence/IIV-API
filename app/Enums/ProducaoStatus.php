<?php

namespace App\Enums;

/** Partilhado por `Lote` e `Plano` — mesmo conjunto de estados nos dois DTOs. */
enum ProducaoStatus: string
{
    case Planeada = 'planeada';
    case EmProducao = 'em_producao';
    case Concluida = 'concluida';
    case Suspensa = 'suspensa';
}
