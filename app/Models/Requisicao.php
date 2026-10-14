<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\RequisicaoStatus;
use Database\Factories\RequisicaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Requisição = processo laboratorial aberto na CGISC. As áreas de destino
 * derivam dos exames pedidos (`exames()`), não de `laboratorio_id`.
 */
class Requisicao extends Model
{
    /** @use HasFactory<RequisicaoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'requisicoes';

    protected $fillable = [
        'id', 'numero', 'laboratorio_id', 'tipo_sujeito', 'cliente_nome', 'cliente_contacto',
        'veterinario_responsavel', 'dados_epidemiologicos', 'dados_facturacao',
        'consentimento', 'assinatura_cliente', 'created_by',
        'status', 'origem_tipo', 'proveniencia_local', 'termo_entrega_numero',
        'termo_entrega_assinado_por', 'entregue_por', 'entregue_em', 'recebida_por_id',
        'conservacao', 'colheita_em', 'doencas_suspeitas', 'pat_numero',
        'processo_aberto_em', 'encerrada_em', 'encerrada_por_id',
    ];

    protected $attributes = [
        'status' => 'registada',
        'proveniencia_local' => true,
    ];

    protected function casts(): array
    {
        return [
            'dados_epidemiologicos' => 'array',
            'dados_facturacao' => 'array',
            'consentimento' => 'boolean',
            'status' => RequisicaoStatus::class,
            'proveniencia_local' => 'boolean',
            'entregue_em' => 'datetime',
            'colheita_em' => 'datetime',
            'processo_aberto_em' => 'datetime',
            'encerrada_em' => 'datetime',
        ];
    }

    /** @deprecated A área deriva agora dos exames pedidos (ver exames()). Só dados antigos. */
    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recebidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recebida_por_id');
    }

    public function amostras(): HasMany
    {
        return $this->hasMany(Amostra::class);
    }

    public function exames(): HasMany
    {
        return $this->hasMany(RequisicaoExame::class);
    }

    public function boletinsInternos(): HasMany
    {
        return $this->hasMany(BoletimInterno::class);
    }

    public function boletinsAnalises(): HasMany
    {
        return $this->hasMany(BoletimAnalise::class);
    }

    public function inqueritosSatisfacao(): HasMany
    {
        return $this->hasMany(InqueritoSatisfacao::class);
    }
}
