<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\ProdutoType;
use Database\Factories\ProdutoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    /** @use HasFactory<ProdutoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = ['id', 'name', 'product_type', 'description', 'unit', 'is_archived', 'created_at'];

    protected function casts(): array
    {
        return [
            'product_type' => ProdutoType::class,
            'is_archived' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function planos(): HasMany
    {
        return $this->hasMany(Plano::class);
    }
}
