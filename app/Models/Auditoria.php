<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\AuditoriaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auditoria extends Model
{
    /** @use HasFactory<AuditoriaFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = [
        'id', 'title', 'audit_type', 'auditor', 'scheduled_date', 'completed_date',
        'status', 'findings', 'recommendations', 'department_id', 'laboratory_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class, 'laboratory_id');
    }

    public function naoConformidades(): HasMany
    {
        return $this->hasMany(NaoConformidade::class, 'audit_id');
    }
}
