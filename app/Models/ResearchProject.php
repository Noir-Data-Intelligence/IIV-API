<?php

namespace App\Models;

use Database\Factories\ResearchProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchProject extends Model
{
    /** @use HasFactory<ResearchProjectFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'line_id', 'title', 'objectives', 'status', 'start_date', 'end_date',
        'funding_source', 'funding_amount', 'currency', 'partners', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'funding_amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(ResearchLine::class, 'line_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class, 'project_id');
    }
}
