<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\InsumoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insumo extends Model
{
    /** @use HasFactory<InsumoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = ['id', 'laboratorio_id', 'name', 'quantity', 'unit', 'min_stock', 'expiry_date'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_stock' => 'integer',
            'expiry_date' => 'date',
        ];
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }
}
