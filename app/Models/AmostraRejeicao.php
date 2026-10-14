<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Termo de Rejeição (fase recepcao|area), com 2 assinaturas distintas. */
class AmostraRejeicao extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'amostra_rejeicoes';

    protected $fillable = [
        'id', 'amostra_id', 'criterio_rejeicao_id', 'responsavel_id',
        'detalhe', 'assinatura_responsavel', 'rejeitada_em',
        'laboratorio_id', 'fase', 'boletim_interno_id', 'segundo_responsavel_id',
        'assinatura_segundo', 'origem_notificada_em', 'accao_tomada',
    ];

    protected function casts(): array
    {
        return ['rejeitada_em' => 'datetime', 'origem_notificada_em' => 'datetime'];
    }

    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function criterioRejeicao(): BelongsTo
    {
        return $this->belongsTo(CriterioRejeicao::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function segundoResponsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'segundo_responsavel_id');
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }
}
