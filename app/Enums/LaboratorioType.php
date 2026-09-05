<?php

namespace App\Enums;

/**
 * Classificação da área/disciplina do laboratório (campo "Tipo"). Sem dados
 * reais em produção para levantar (o seeder das 9 áreas não preenche este
 * campo — ver `LaboratorioSeeder`); valores por omissão cobrindo as
 * disciplinas veterinárias mais comuns, incluindo "Outro" para não bloquear
 * casos não previstos (ver auditoria funcional — Laboratórios/Tipo).
 */
enum LaboratorioType: string
{
    case Bacteriologia = 'bacteriologia';
    case Virologia = 'virologia';
    case Parasitologia = 'parasitologia';
    case Serologia = 'serologia';
    case BiologiaMolecular = 'biologia_molecular';
    case Bromatologia = 'bromatologia';
    case Patologia = 'patologia';
    case Outro = 'outro';
}
