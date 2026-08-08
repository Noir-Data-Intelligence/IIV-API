<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use App\Enums\ProducaoStatus;
use Database\Factories\PlanoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plano extends Model
{
    /** @use HasFactory<PlanoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $table = 'planeamento';

    protected $fillable = [
        'id', 'product_id', 'planned_quantity', 'actual_quantity',
        'planned_start', 'planned_end', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'integer',
            'actual_quantity' => 'integer',
            'planned_start' => 'date',
            'planned_end' => 'date',
            'status' => ProducaoStatus::class,
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'product_id');
    }
}
