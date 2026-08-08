<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\NaoConformidadeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NaoConformidade extends Model
{
    /** @use HasFactory<NaoConformidadeFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $table = 'nao_conformidades';

    protected $fillable = [
        'id', 'title', 'description', 'severity', 'status', 'corrective_action',
        'deadline', 'resolved_at', 'department_id', 'audit_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function auditoria(): BelongsTo
    {
        return $this->belongsTo(Auditoria::class, 'audit_id');
    }
}
