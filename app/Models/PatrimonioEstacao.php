<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\PatrimonioEstacaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrimonioEstacao extends Model
{
    /** @use HasFactory<PatrimonioEstacaoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'patrimonio_estacao';

    protected $fillable = [
        'id', 'code', 'name', 'category', 'description', 'location', 'station_id',
        'department_id', 'responsible_user', 'acquisition_date', 'acquisition_cost',
        'current_value', 'serial_number', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'current_value' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manutencoes(): HasMany
    {
        return $this->hasMany(PatrimonioEstacaoManutencao::class, 'patrimonio_estacao_id')->orderByDesc('date');
    }
}
