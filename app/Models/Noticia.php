<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Noticia extends Model
{
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'slug', 'titulo', 'resumo', 'conteudo', 'categoria',
        'image_path', 'destaque', 'published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'destaque' => 'boolean',
            'published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
