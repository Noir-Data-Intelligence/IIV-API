<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Assinatura de validação de um BI (tipo: segundo|terceiro|hierarquica|qualidade). */
class Validacao extends Model
{
    use HasUuids, LogsAuditTrail;

    public const UPDATED_AT = null;

    protected $table = 'validacoes';

    protected $fillable = ['id', 'boletim_interno_id', 'validador_id', 'ordem', 'tipo', 'assinatura', 'observacao'];

    protected function casts(): array
    {
        return ['ordem' => 'integer', 'created_at' => 'datetime'];
    }

    public function boletimInterno(): BelongsTo
    {
        return $this->belongsTo(BoletimInterno::class);
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id');
    }
}
