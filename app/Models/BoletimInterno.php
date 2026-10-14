<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\BoletimInternoStatus;
use Database\Factories\BoletimInternoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Boletim Interno: 1 por (requisição × área), com N amostras — decisão
 * D.0#1 (a confirmar com o laboratório). `numero` = PAT-###-ANO/{código área}.
 * `status` só muda através dos serviços App\Services\Lab.
 */
class BoletimInterno extends Model
{
    /** @use HasFactory<BoletimInternoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'boletins_internos';

    protected $fillable = [
        'id', 'amostra_id', 'numero_analise', 'exames',
        'entrada_em', 'inicio_em', 'conclusao_em', 'status',
        'requisicao_id', 'laboratorio_id', 'numero', 'prazo_em', 'resultado_global',
        'encaminhado_por_id', 'encaminhado_em', 'recebido_area_por_id', 'recebido_area_em',
        'responsavel_tecnico_id', 'devolvido_por_id', 'devolvido_em', 'recebido_cgisc_por_id',
        'recebido_cgisc_em', 'motivo_correccao', 'bi_origem_id',
    ];

    protected $attributes = [
        'status' => 'gerado',
    ];

    protected function casts(): array
    {
        return [
            'exames' => 'array',
            'entrada_em' => 'datetime',
            'inicio_em' => 'datetime',
            'conclusao_em' => 'datetime',
            'prazo_em' => 'datetime',
            'encaminhado_em' => 'datetime',
            'recebido_area_em' => 'datetime',
            'devolvido_em' => 'datetime',
            'recebido_cgisc_em' => 'datetime',
            'status' => BoletimInternoStatus::class,
        ];
    }

    /** @deprecated Modelo antigo (1 BI por amostra). Usar amostras(). */
    public function amostra(): BelongsTo
    {
        return $this->belongsTo(Amostra::class);
    }

    public function amostras(): BelongsToMany
    {
        return $this->belongsToMany(Amostra::class, 'boletim_interno_amostras')
            ->using(BoletimInternoAmostra::class)
            ->withPivot('id', 'estado')
            ->withTimestamps();
    }

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(Requisicao::class);
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }

    public function examesPedidos(): HasMany
    {
        return $this->hasMany(RequisicaoExame::class);
    }

    public function ensaios(): HasMany
    {
        return $this->hasMany(Ensaio::class);
    }

    public function validacoes(): HasMany
    {
        return $this->hasMany(Validacao::class)->orderBy('ordem');
    }

    public function boletimAnalise(): HasOne
    {
        return $this->hasOne(BoletimAnalise::class);
    }

    public function biOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'bi_origem_id');
    }

    public function responsavelTecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_tecnico_id');
    }
}
