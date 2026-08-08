<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmostraRejeicao extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'amostra_rejeicoes';

    protected $fillable = [
        'id', 'amostra_id', 'criterio_rejeicao_id', 'responsavel_id',
        'detalhe', 'assinatura_responsavel', 'rejeitada_em',
    ];

    protected function casts(): array
    {
        return ['rejeitada_em' => 'datetime'];
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
}
