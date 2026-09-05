<?php

namespace App\Models;

use Database\Factories\FormacaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formacao extends Model
{
    /** @use HasFactory<FormacaoFactory> */
    use HasFactory, HasUuids;

    // Sem isto, a pluralização automática do Eloquent (inglesa) gera
    // "formacaos" a partir de "Formacao" — a tabela real, criada na
    // migration, é "formacoes". Sem `$table` explícito, toda e qualquer
    // query ao modelo falhava com erro SQL (causa raiz de "não permite
    // criar formação" na auditoria funcional).
    protected $table = 'formacoes';

    public $timestamps = false;

    protected $fillable = [
        'id', 'title', 'description', 'trainer', 'location', 'start_date', 'end_date',
        'hours', 'status', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'hours' => 'decimal:1',
            'created_at' => 'datetime',
        ];
    }
}
