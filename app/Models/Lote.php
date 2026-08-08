<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\ProducaoStatus;
use Database\Factories\LoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    /** @use HasFactory<LoteFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = [
        'id', 'batch_number', 'product_id', 'quantity_produced', 'quantity_distributed',
        'production_date', 'expiry_date', 'status', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_produced' => 'integer',
            'quantity_distributed' => 'integer',
            'production_date' => 'date',
            'expiry_date' => 'date',
            'status' => ProducaoStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'product_id');
    }

    public function distribuicoes(): HasMany
    {
        return $this->hasMany(Distribuicao::class, 'batch_id');
    }
}
