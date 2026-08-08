<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\BoletimAnaliseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletimAnalise extends Model
{
    /** @use HasFactory<BoletimAnaliseFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'boletins_analises';

    protected $fillable = [
        'id', 'boletim_interno_id', 'resultado',
        'validador_1_id', 'validador_2_id', 'validador_3_id', 'validado_em',
        'aprovado_por_id', 'aprovado_em', 'comunicado_em',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => 'array',
            'validado_em' => 'datetime',
            'aprovado_em' => 'datetime',
            'comunicado_em' => 'datetime',
        ];
    }

    public function boletimInterno(): BelongsTo
    {
        return $this->belongsTo(BoletimInterno::class);
    }

    public function validador1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_1_id');
    }

    public function validador2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_2_id');
    }

    public function validador3(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_3_id');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    /** @return list<string> IDs (não nulos) dos validadores já registados. */
    public function validadorIds(): array
    {
        return array_values(array_filter([
            $this->validador_1_id, $this->validador_2_id, $this->validador_3_id,
        ]));
    }
}
