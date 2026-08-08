<?php

namespace App\Models;

use Database\Factories\FieldFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    /** @use HasFactory<FieldFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'station_id', 'crop_id', 'field_code', 'area_ha', 'planting_date',
        'expected_harvest', 'status', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'area_ha' => 'decimal:2',
            'planting_date' => 'date',
            'expected_harvest' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'station_id');
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class, 'crop_id');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class, 'field_id')->orderByDesc('harvest_date');
    }
}
