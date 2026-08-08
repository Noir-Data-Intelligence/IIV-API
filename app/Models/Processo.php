<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\ProcessoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    /** @use HasFactory<ProcessoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = [
        'id', 'code', 'type_id', 'title', 'description', 'requester_id', 'status',
        'priority', 'due_date', 'opened_at', 'closed_at', 'current_step_id',
        'linked_entity_type', 'linked_entity_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProcessType::class, 'type_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class, 'process_id')->orderBy('order_index');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProcessEvent::class, 'process_id')->orderByDesc('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProcessAttachment::class, 'process_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class, 'current_step_id');
    }
}
