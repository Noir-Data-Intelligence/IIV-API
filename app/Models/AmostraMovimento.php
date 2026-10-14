<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimento de rastreabilidade da amostra. Só é criado pelos serviços de
 * App\Services\Lab (append-only — nunca editado nem apagado pela API).
 */
class AmostraMovimento extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    public const ACCOES = [
        'recebida', 'conferida', 'aceite', 'rejeitada', 'encaminhada', 'recebida_area',
        'ensaio_iniciado', 'resultado', 'validada', 'devolvida', 'recebida_cgisc',
        'devolvida_correccao', 'emitida', 'entregue', 'reencaminhada',
    ];

    protected $table = 'amostra_movimentos';

    protected $fillable = ['id', 'amostra_id', 'boletim_interno_id', 'accao', 'de_local', 'para_local', 'user_id', 'observacao'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function boletimInterno(): BelongsTo
    {
        return $this->belongsTo(BoletimInterno::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
