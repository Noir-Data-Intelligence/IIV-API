<?php

namespace App\Models;

use Database\Factories\RhLaboratorioContratoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhLaboratorioContrato extends Model
{
    /** @use HasFactory<RhLaboratorioContratoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'rh_laboratorio_contratos';

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
        return $this->belongsTo(RhLaboratorioColaborador::class, 'employee_id');
    }
}
