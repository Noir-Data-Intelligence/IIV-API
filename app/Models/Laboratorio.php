<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\LaboratorioFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratorio extends Model
{
    /** @use HasFactory<LaboratorioFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'code', 'name', 'type', 'description',
        'validador_count', 'sla_horas', 'validadores_nomeados', 'is_active',
        'sla_rule_type', 'sla_horas_negativo', 'sla_dias', 'sla_active', 'sla_severity',
        // D.0#4/#5 — a confirmar com o laboratório (ver migration 2026_10_14_101200).
        'responsavel_qualidade_id', 'exige_pagamento_para_entrega',
    ];

    protected function casts(): array
    {
        return [
            'validador_count' => 'integer',
            'sla_horas' => 'integer',
            'validadores_nomeados' => 'array',
            'is_active' => 'boolean',
            'sla_horas_negativo' => 'integer',
            'sla_dias' => 'integer',
            'sla_active' => 'boolean',
            'exige_pagamento_para_entrega' => 'boolean',
        ];
    }

    public function responsavelQualidade(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_qualidade_id');
    }

    public function exames(): HasMany
    {
        return $this->hasMany(ExameCatalogo::class);
    }

    /** O utilizador pertence à equipa desta área (pivot `laboratorio_tecnicos`)? */
    public function temMembro(User $user): bool
    {
        return $this->tecnicos()->where('users.id', $user->id)->exists();
    }

    public function requisicoes(): HasMany
    {
        return $this->hasMany(Requisicao::class);
    }

    public function criteriosRejeicao(): HasMany
    {
        return $this->hasMany(CriterioRejeicao::class);
    }

    public function tecnicos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'laboratorio_tecnicos')
            ->using(LaboratorioTecnico::class)
            ->withPivot('funcao')
            ->withTimestamps();
    }
}
