<?php

namespace App\Models;

use Database\Factories\EvaluationCriteriaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationCriteria extends Model
{
    /** @use HasFactory<EvaluationCriteriaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'evaluation_criteria';

    public $timestamps = false;

    protected $fillable = [
        'id', 'cycle_id', 'name', 'weight', 'description', 'display_order', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'display_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
    }
}
