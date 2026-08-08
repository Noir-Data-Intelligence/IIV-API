<?php

namespace App\Http\Resources;

use App\Models\RhLaboratorioColaborador;
use App\Models\RhTransversalColaborador;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cycleId' => $this->cycle_id,
            'cycleName' => $this->cycle?->name,
            'employeeId' => $this->employee_id,
            'employeeName' => $this->resolverNomeColaborador(),
            'evaluatorId' => $this->evaluator_id,
            'evaluatorName' => $this->evaluator?->full_name,
            'evaluationDate' => $this->evaluation_date?->toDateString(),
            'globalScore' => $this->global_score !== null ? (float) $this->global_score : null,
            'strengths' => $this->strengths,
            'improvements' => $this->improvements,
            'generalComments' => $this->general_comments,
            'status' => $this->status,
            'submittedAt' => $this->submitted_at?->toISOString(),
            'approvedAt' => $this->approved_at?->toISOString(),
            'approvedBy' => $this->approved_by,
            'approvedByName' => $this->approver?->full_name,
            'rejectionReason' => $this->rejection_reason,
            'acknowledgedAt' => $this->acknowledged_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * `employee_id` não tem FK (dualidade RH transversal/laboratorial, ver
     * secção 6 da memória do projecto) — resolve o nome procurando nos dois
     * esquemas de colaboradores.
     */
    private function resolverNomeColaborador(): ?string
    {
        return RhTransversalColaborador::query()->whereKey($this->employee_id)->value('full_name')
            ?? RhLaboratorioColaborador::query()->whereKey($this->employee_id)->value('full_name');
    }
}
