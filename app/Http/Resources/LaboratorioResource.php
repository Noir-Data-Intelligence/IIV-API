<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratorioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'validadorCount' => $this->validador_count,
            'slaHoras' => $this->sla_horas,
            'validadoresNomeados' => $this->validadores_nomeados,
            'isActive' => (bool) $this->is_active,
            'slaRuleType' => $this->sla_rule_type,
            'slaHorasNegativo' => $this->sla_horas_negativo,
            'slaDias' => $this->sla_dias,
            'slaActive' => (bool) $this->sla_active,
            'slaSeverity' => $this->sla_severity,
            // D.0#4/#5 — a confirmar com o laboratório.
            'responsavelQualidadeId' => $this->responsavel_qualidade_id,
            'exigePagamentoParaEntrega' => (bool) $this->exige_pagamento_para_entrega,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
