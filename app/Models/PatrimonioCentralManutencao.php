<?php

namespace App\Models;

use Database\Factories\PatrimonioCentralManutencaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrimonioCentralManutencao extends Model
{
    /** @use HasFactory<PatrimonioCentralManutencaoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'patrimonio_central_manutencoes';

    public $timestamps = false;

    protected $fillable = [
        'id', 'patrimonio_central_id', 'date', 'type', 'description', 'cost',
        'provider', 'next_due_date', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'cost' => 'decimal:2',
            'next_due_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function activo(): BelongsTo
    {
        return $this->belongsTo(PatrimonioCentral::class, 'patrimonio_central_id');
    }
}
