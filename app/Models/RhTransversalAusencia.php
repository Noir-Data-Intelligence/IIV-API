<?php

namespace App\Models;

use Database\Factories\RhTransversalAusenciaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhTransversalAusencia extends Model
{
    /** @use HasFactory<RhTransversalAusenciaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'rh_transversal_ausencias';

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
        return $this->belongsTo(RhTransversalColaborador::class, 'employee_id');
    }
}
