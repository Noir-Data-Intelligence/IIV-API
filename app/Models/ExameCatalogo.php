<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\ExameCatalogoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exame do catálogo. `campos_resultado` é o schema dos parâmetros do
 * resultado, ex: [{"chave":"salmonela","tipo":"numero","obrigatorio":true}].
 * Tipos suportados: numero | texto | booleano | opcao (com "opcoes").
 */
class ExameCatalogo extends Model
{
    /** @use HasFactory<ExameCatalogoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'exames_catalogo';

    protected $fillable = [
        'id', 'laboratorio_id', 'codigo', 'nome', 'tecnica_metodo', 'pop_codigo',
        'tipos_amostra', 'sla_horas', 'campos_resultado', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipos_amostra' => 'array',
            'campos_resultado' => 'array',
            'sla_horas' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }
}
