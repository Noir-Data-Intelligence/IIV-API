<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\ContaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    /** @use HasFactory<ContaFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = ['id', 'code', 'name', 'type', 'description', 'is_active', 'created_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'account_id');
    }

    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'account_id');
    }
}
