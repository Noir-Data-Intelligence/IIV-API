<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Exame pedido numa requisição (amostra_id null = todas as amostras). */
class RequisicaoExame extends Model
{
    use HasUuids;

    protected $table = 'requisicao_exames';

    protected $fillable = ['id', 'requisicao_id', 'amostra_id', 'exame_id', 'laboratorio_id', 'boletim_interno_id'];

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(Requisicao::class);
    }

    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function exame(): BelongsTo
    {
        return $this->belongsTo(ExameCatalogo::class, 'exame_id');
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }

    public function boletimInterno(): BelongsTo
    {
        return $this->belongsTo(BoletimInterno::class);
    }

    /** O exame aplica-se à amostra indicada? */
    public function aplicaA(string $amostraId): bool
    {
        return $this->amostra_id === null || $this->amostra_id === $amostraId;
    }
}
