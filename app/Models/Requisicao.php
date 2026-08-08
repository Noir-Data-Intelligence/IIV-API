<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\RequisicaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisicao extends Model
{
    /** @use HasFactory<RequisicaoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'requisicoes';

    protected $fillable = [
        'id', 'numero', 'laboratorio_id', 'tipo_sujeito', 'cliente_nome', 'cliente_contacto',
        'veterinario_responsavel', 'dados_epidemiologicos', 'dados_facturacao',
        'consentimento', 'assinatura_cliente', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dados_epidemiologicos' => 'array',
            'dados_facturacao' => 'array',
            'consentimento' => 'boolean',
        ];
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amostras(): HasMany
    {
        return $this->hasMany(Amostra::class);
    }

    public function inqueritosSatisfacao(): HasMany
    {
        return $this->hasMany(InqueritoSatisfacao::class);
    }
}
