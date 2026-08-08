<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'nome', 'email', 'assunto', 'mensagem', 'lida', 'respondida', 'created_at'];

    protected function casts(): array
    {
        return [
            'lida' => 'boolean',
            'respondida' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
