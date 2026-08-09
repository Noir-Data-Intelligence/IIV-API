<?php

namespace App\Services;

use App\Models\Missao;
use Illuminate\Validation\ValidationException;

/**
 * 3ª máquina de estados do sistema (a 1ª é o `LabWorkflowService` da Onda 3,
 * a 2ª o `ProcessWorkflowService` da Onda 6). Replica exactamente
 * `mocks/handlers/missoes.ts`: `planeada -> submetida -> aprovada` via
 * `submeter`/`aprovar`; `rejeitar` devolve a missão a `planeada` (não existe
 * estado "rejeitada" para missões). Transições fora do estado esperado
 * devolvem 409 — não existem endpoints dedicados para `em_curso`/
 * `concluida`/`cancelada`, essas ficam a cargo do `PUT` genérico.
 */
class MissionWorkflowService
{
    public function submeter(Missao $missao): Missao
    {
        return $this->transitar($missao, 'planeada', 'submetida');
    }

    /**
     * Grava sempre quem aprovou — antes disto a missão passava a "aprovada"
     * sem nenhuma atribuição, tornando a decisão impossível de auditar
     * depois (auditoria de segurança, 2026-08-09).
     */
    public function aprovar(Missao $missao, string $approverId): Missao
    {
        return $this->transitar($missao, 'submetida', 'aprovada', ['approved_by' => $approverId]);
    }

    public function rejeitar(Missao $missao): Missao
    {
        return $this->transitar($missao, 'submetida', 'planeada');
    }

    /** @param  array<string, mixed>  $patch */
    private function transitar(Missao $missao, string $de, string $para, array $patch = []): Missao
    {
        if ($missao->status !== $de) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida a partir do estado \"{$missao->status}\"."],
            ])->status(409);
        }

        $missao->update(['status' => $para, ...$patch]);

        return $missao->fresh();
    }
}
