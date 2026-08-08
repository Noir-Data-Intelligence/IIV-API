<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\BoletimStatus;
use Database\Factories\BoletimInternoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoletimInterno extends Model
{
    /** @use HasFactory<BoletimInternoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'boletins_internos';

    protected $fillable = [
        'id', 'amostra_id', 'numero_analise', 'exames',
        'entrada_em', 'inicio_em', 'conclusao_em', 'status',
    ];

    protected function casts(): array
    {
        return [
            'exames' => 'array',
            'entrada_em' => 'datetime',
            'inicio_em' => 'datetime',
            'conclusao_em' => 'datetime',
            'status' => BoletimStatus::class,
        ];
    }

    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function boletimAnalise(): HasOne
    {
        return $this->hasOne(BoletimAnalise::class);
    }
}
