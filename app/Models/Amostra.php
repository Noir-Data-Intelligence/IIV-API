<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\AmostraStatus;
use Database\Factories\AmostraFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Amostra extends Model
{
    /** @use HasFactory<AmostraFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'requisicao_id', 'numero', 'tipo_amostra', 'origem_matriz',
        'ponto_colheita', 'latitude', 'longitude', 'colhida_por', 'sujeito', 'recebida_em', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sujeito' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'recebida_em' => 'datetime',
            'status' => AmostraStatus::class,
        ];
    }

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(Requisicao::class);
    }

    public function boletimInterno(): HasOne
    {
        return $this->hasOne(BoletimInterno::class);
    }

    public function rejeicao(): HasOne
    {
        return $this->hasOne(AmostraRejeicao::class);
    }
}
