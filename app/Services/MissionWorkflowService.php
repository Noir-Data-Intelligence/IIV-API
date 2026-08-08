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

    public function aprovar(Missao $missao): Missao
    {
        return $this->transitar($missao, 'submetida', 'aprovada');
    }

    public function rejeitar(Missao $missao): Missao
    {
        return $this->transitar($missao, 'submetida', 'planeada');
    }

    private function transitar(Missao $missao, string $de, string $para): Missao
    {
        if ($missao->status !== $de) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida a partir do estado \"{$missao->status}\"."],
            ])->status(409);
        }

        $missao->update(['status' => $para]);

        return $missao->fresh();
    }
}
