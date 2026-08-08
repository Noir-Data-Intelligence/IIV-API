<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Legislacao extends Model
{
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'legislacao';

    protected $fillable = ['id', 'num', 'slug', 'titulo', 'descricao', 'tipo', 'ano', 'pdf_url', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
