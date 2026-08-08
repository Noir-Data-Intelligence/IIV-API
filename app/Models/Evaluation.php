<?php

namespace App\Models;

use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'cycle_id', 'employee_id', 'evaluator_id', 'evaluation_date', 'global_score',
        'strengths', 'improvements', 'general_comments', 'status', 'submitted_at',
        'approved_at', 'approved_by', 'rejection_reason', 'acknowledged_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'global_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class, 'evaluation_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(EvaluationHistory::class, 'evaluation_id')->orderByDesc('created_at');
    }
}
