<?php

namespace App\Models;

use Database\Factories\CriterioRejeicaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterioRejeicao extends Model
{
    /** @use HasFactory<CriterioRejeicaoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'criterios_rejeicao';

    protected $fillable = ['id', 'laboratorio_id', 'codigo', 'motivo'];

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }
}
