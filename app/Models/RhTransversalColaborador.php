<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\RhTransversalColaboradorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RhTransversalColaborador extends Model
{
    /** @use HasFactory<RhTransversalColaboradorFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'rh_transversal_colaboradores';

    public $timestamps = false;

    protected $fillable = [
        'id', 'employee_number', 'full_name', 'national_id', 'phone', 'email',
        'department_id', 'hire_date', 'is_active', 'qualifications', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(RhTransversalContrato::class, 'employee_id');
    }

    public function ausencias(): HasMany
    {
        return $this->hasMany(RhTransversalAusencia::class, 'employee_id');
    }
}
