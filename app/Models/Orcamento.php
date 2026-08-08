<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\OrcamentoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Orcamento extends Model
{
    /** @use HasFactory<OrcamentoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = [
        'id', 'year', 'account_id', 'department_id', 'planned_amount', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'planned_amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'account_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
