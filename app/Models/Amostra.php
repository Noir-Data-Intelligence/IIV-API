<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\AmostraStatus;
use Database\Factories\AmostraFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Amostra extends Model
{
    /** @use HasFactory<AmostraFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'requisicao_id', 'numero', 'tipo_amostra', 'origem_matriz',
        'ponto_colheita', 'latitude', 'longitude', 'colhida_por', 'sujeito', 'recebida_em', 'status',
        'aceite_em', 'aceite_com_reserva', 'reserva_motivo', 'codigo_barras', 'amostra_origem_id',
        'localizacao_actual',
    ];

    protected function casts(): array
    {
        return [
            'sujeito' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'recebida_em' => 'datetime',
            'aceite_em' => 'datetime',
            'aceite_com_reserva' => 'boolean',
            'status' => AmostraStatus::class,
        ];
    }

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(Requisicao::class);
    }

    /** @deprecated Modelo antigo (1 BI por amostra). Usar boletinsInternos(). */
    public function boletimInterno(): HasOne
    {
        return $this->hasOne(BoletimInterno::class);
    }

    public function boletinsInternos(): BelongsToMany
    {
        return $this->belongsToMany(BoletimInterno::class, 'boletim_interno_amostras')
            ->using(BoletimInternoAmostra::class)
            ->withPivot('id', 'estado')
            ->withTimestamps();
    }

    /** Última rejeição registada (recepção ou área). */
    public function rejeicao(): HasOne
    {
        return $this->hasOne(AmostraRejeicao::class)->latestOfMany('rejeitada_em');
    }

    public function rejeicoes(): HasMany
    {
        return $this->hasMany(AmostraRejeicao::class);
    }

    public function conferencias(): HasMany
    {
        return $this->hasMany(AmostraConferencia::class)->orderBy('ordem');
    }

    public function movimentos(): HasMany
    {
        return $this->hasMany(AmostraMovimento::class)->orderBy('created_at');
    }

    public function amostraOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'amostra_origem_id');
    }
}
