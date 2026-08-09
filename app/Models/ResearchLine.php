<?php

namespace App\Models;

use Database\Factories\ResearchLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchLine extends Model
{
    /** @use HasFactory<ResearchLineFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'name', 'area', 'description', 'status', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ResearchProject::class, 'line_id');
    }
}
