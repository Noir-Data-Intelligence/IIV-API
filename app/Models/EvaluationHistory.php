<?php

namespace App\Models;

use Database\Factories\EvaluationHistoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationHistory extends Model
{
    /** @use HasFactory<EvaluationHistoryFactory> */
    use HasFactory, HasUuids;

    protected $table = 'evaluation_history';

    public $timestamps = false;

    protected $fillable = [
        'id', 'evaluation_id', 'actor_id', 'action', 'from_status', 'to_status',
        'comment', 'changes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
