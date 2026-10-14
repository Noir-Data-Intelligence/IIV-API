<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\BoletimAnaliseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Boletim de Análises OFICIAL — emitido só pela CGISC a partir de um BI
 * recebido de volta (EmissaoService::emitirBoletimAnalises). `conteudo` é o
 * snapshot consolidado no momento da emissão (imutável).
 *
 * Campos @deprecated (modelo antigo, mantidos só para dados existentes):
 * `resultado`, `validador_1_id`, `validador_2_id`, `validador_3_id`,
 * `validado_em`, `aprovado_por_id`, `aprovado_em`, `comunicado_em`. As
 * validações vivem agora na tabela `validacoes` (por BI).
 */
class BoletimAnalise extends Model
{
    /** @use HasFactory<BoletimAnaliseFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'boletins_analises';

    protected $fillable = [
        'id', 'boletim_interno_id', 'resultado',
        'validador_1_id', 'validador_2_id', 'validador_3_id', 'validado_em',
        'aprovado_por_id', 'aprovado_em', 'comunicado_em',
        'numero', 'requisicao_id', 'laboratorio_id', 'conteudo', 'emitido_por_id', 'emitido_em',
        'assinado_por_id', 'assinatura_hash', 'pdf_path', 'entregue_em', 'entregue_a',
        'canal_entrega', 'isv_notificado_em',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => 'array',
            'conteudo' => 'array',
            'validado_em' => 'datetime',
            'aprovado_em' => 'datetime',
            'comunicado_em' => 'datetime',
            'emitido_em' => 'datetime',
            'entregue_em' => 'datetime',
            'isv_notificado_em' => 'datetime',
        ];
    }

    public function boletimInterno(): BelongsTo
    {
        return $this->belongsTo(BoletimInterno::class);
    }

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(Requisicao::class);
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }

    public function emitidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por_id');
    }

    /** @deprecated */
    public function validador1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_1_id');
    }

    /** @deprecated */
    public function validador2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_2_id');
    }

    /** @deprecated */
    public function validador3(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_3_id');
    }

    /** @deprecated */
    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    /**
     * @deprecated Usar BoletimInterno::validacoes().
     *
     * @return list<string>
     */
    public function validadorIds(): array
    {
        return array_values(array_filter([
            $this->validador_1_id, $this->validador_2_id, $this->validador_3_id,
        ]));
    }
}
