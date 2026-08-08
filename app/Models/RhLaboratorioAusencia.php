<?php

namespace App\Models;

use Database\Factories\RhLaboratorioAusenciaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhLaboratorioAusencia extends Model
{
    /** @use HasFactory<RhLaboratorioAusenciaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'rh_laboratorio_ausencias';

    public $timestamps = false;

    protected $fillable = [
        'id', 'employee_id', 'leave_type', 'start_date', 'end_date',
        'days', 'status', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(RhLaboratorioColaborador::class, 'employee_id');
    }
}
