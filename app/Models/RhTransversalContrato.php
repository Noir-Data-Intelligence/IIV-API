<?php

namespace App\Models;

use Database\Factories\RhTransversalContratoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhTransversalContrato extends Model
{
    /** @use HasFactory<RhTransversalContratoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'rh_transversal_contratos';

    public $timestamps = false;

    protected $fillable = [
        'id', 'employee_id', 'contract_type', 'position', 'start_date', 'end_date',
        'salary', 'currency', 'is_active', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(RhTransversalColaborador::class, 'employee_id');
    }
}
