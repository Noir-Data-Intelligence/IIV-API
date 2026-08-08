<?php

namespace App\Enums;

/**
 * As 3 fases universais da RN "3 fases universais" (pré-analítica já
 * coberta por AmostraStatus) colapsadas na fase analítica/pós-analítica de
 * um único boletim interno, parametrizadas por disciplina em vez de 8
 * máquinas de estado duplicadas (ver LabWorkflowService).
 */
enum BoletimStatus: string
{
    case EmAnalise = 'em_analise';
    case ResultadoRegistado = 'resultado_registado';
    case EmValidacao = 'em_validacao';
    case Aprovado = 'aprovado';
    case Comunicado = 'comunicado';
}
