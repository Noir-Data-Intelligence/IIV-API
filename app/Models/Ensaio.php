<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Boletim de ensaio (interno à área) — 1 por BI × amostra × exame. */
class Ensaio extends Model
{
    use HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'boletim_interno_id', 'amostra_id', 'exame_id', 'tecnica', 'pop_codigo',
        'equipamento_id', 'lote_reagente', 'executor_id', 'segundo_interveniente_id',
        'visualizador_id', 'iniciado_em', 'concluido_em', 'resultado', 'resultado_qualitativo',
        'interpretacao',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => 'array',
            'iniciado_em' => 'datetime',
            'concluido_em' => 'datetime',
        ];
    }

    public function boletimInterno(): BelongsTo
    {
        return $this->belongsTo(BoletimInterno::class);
    }

    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function exame(): BelongsTo
    {
        return $this->belongsTo(ExameCatalogo::class, 'exame_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }
}
