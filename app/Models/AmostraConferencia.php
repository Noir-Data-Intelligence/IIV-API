<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Conferência de conformidade na recepção (ordem 1 ou 2 — técnicos distintos). */
class AmostraConferencia extends Model
{
    use HasUuids, LogsAuditTrail;

    public const UPDATED_AT = null;

    /** Os 6 critérios de conformidade da recepção (coluna => rótulo). */
    public const CRITERIOS = [
        'crit_identificacao' => 'Identificação',
        'crit_quantidade' => 'Quantidade',
        'crit_conservacao' => 'Conservação',
        'crit_macroscopica' => 'Análise macroscópica',
        'crit_caracteristicas_fisicas' => 'Características físicas',
        'crit_tempo_colheita' => 'Tempo desde a colheita',
    ];

    protected $table = 'amostra_conferencias';

    protected $fillable = [
        'id', 'amostra_id', 'tecnico_id', 'ordem',
        'crit_identificacao', 'crit_quantidade', 'crit_conservacao', 'crit_macroscopica',
        'crit_caracteristicas_fisicas', 'crit_tempo_colheita', 'conforme', 'observacao',
    ];

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'crit_identificacao' => 'boolean',
            'crit_quantidade' => 'boolean',
            'crit_conservacao' => 'boolean',
            'crit_macroscopica' => 'boolean',
            'crit_caracteristicas_fisicas' => 'boolean',
            'crit_tempo_colheita' => 'boolean',
            'conforme' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }
}
