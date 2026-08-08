<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\LancamentoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    /** @use HasFactory<LancamentoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = [
        'id', 'account_id', 'department_id', 'type', 'amount', 'currency',
        'transaction_date', 'description', 'reference', 'status', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
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
