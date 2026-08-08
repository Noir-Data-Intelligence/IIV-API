<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InqueritoSatisfacao extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'inqueritos_satisfacao';

    protected $fillable = ['id', 'requisicao_id', 'dimensoes', 'comentario', 'created_at'];

    protected function casts(): array
    {
        return [
            'dimensoes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function requisicao(): BelongsTo
    {
        return $this->belongsTo(Requisicao::class);
    }
}
