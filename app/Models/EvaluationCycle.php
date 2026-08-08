<?php

namespace App\Models;

use Database\Factories\EvaluationCycleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCycle extends Model
{
    /** @use HasFactory<EvaluationCycleFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'name', 'year', 'start_date', 'end_date', 'status', 'description', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(EvaluationCriteria::class, 'cycle_id')->orderBy('display_order');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'cycle_id');
    }
}
