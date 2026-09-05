<?php

namespace App\Enums;

/**
 * Tipo de amostra recolhida numa Requisição. Sem dados reais em produção
 * para levantar; valores por omissão cobrindo os tipos mais comuns nas
 * fichas em papel (`Documentos/Formulários - IIV/`), incluindo "Outro"
 * para não bloquear casos não previstos (ver auditoria funcional —
 * Requisições/Tipo de Amostra).
 */
enum TipoAmostra: string
{
    case Sangue = 'sangue';
    case Soro = 'soro';
    case Fezes = 'fezes';
    case Urina = 'urina';
    case Zaragatoa = 'zaragatoa';
    case Tecido = 'tecido';
    case Leite = 'leite';
    case Agua = 'agua';
    case Outro = 'outro';
}
