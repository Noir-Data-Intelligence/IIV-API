<?php

namespace Database\Seeders;

use App\Models\CriterioRejeicao;
use App\Models\Laboratorio;
use Illuminate\Database\Seeder;

/**
 * Catálogo de motivos de rejeição — FM-SQ-061 (geral, `laboratorio_id`
 * nulo) e FM-SQ-075 (específico de Microbiologia Alimentar).
 */
class CriterioRejeicaoSeeder extends Seeder
{
    public function run(): void
    {
        $gerais = [
            ['REJ-001', 'Amostra sem identificação ou identificação ilegível'],
            ['REJ-002', 'Volume/quantidade insuficiente para a análise solicitada'],
            ['REJ-003', 'Amostra hemolisada ou coagulada'],
            ['REJ-004', 'Recipiente inadequado ou danificado'],
            ['REJ-005', 'Prazo de transporte/conservação excedido'],
            ['REJ-006', 'Requisição incompleta (falta consentimento ou assinatura)'],
        ];

        foreach ($gerais as [$codigo, $motivo]) {
            CriterioRejeicao::query()->updateOrCreate(
                ['codigo' => $codigo],
                ['laboratorio_id' => null, 'motivo' => $motivo],
            );
        }

        $ama = Laboratorio::query()->where('code', 'AMA')->first();
        if ($ama) {
            $alimentares = [
                ['REJ-101', 'Amostra de água sem registo de temperatura na colheita'],
                ['REJ-102', 'Cadeia de frio quebrada durante o transporte'],
                ['REJ-103', 'Embalagem de alimento violada ou contaminada'],
            ];

            foreach ($alimentares as [$codigo, $motivo]) {
                CriterioRejeicao::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    ['laboratorio_id' => $ama->id, 'motivo' => $motivo],
                );
            }
        }
    }
}
