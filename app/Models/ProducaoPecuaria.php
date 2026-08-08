<?php

namespace App\Models;

use Database\Factories\ProducaoPecuariaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducaoPecuaria extends Model
{
    /** @use HasFactory<ProducaoPecuariaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'producao_pecuaria';

    public $timestamps = false;

    protected $fillable = [
        'id', 'station_id', 'product_type', 'production_date', 'quantity',
        'unit', 'recorded_by', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'quantity' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'station_id');
    }
}
