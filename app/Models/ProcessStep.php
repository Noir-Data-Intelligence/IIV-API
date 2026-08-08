<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessStep extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'process_id', 'type_step_id', 'order_index', 'name', 'assignee_role',
        'status', 'started_at', 'due_at', 'completed_at', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'process_id');
    }
}
