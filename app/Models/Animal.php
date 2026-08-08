<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\AnimalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Animal extends Model
{
    /** @use HasFactory<AnimalFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = [
        'id', 'station_id', 'tag', 'name', 'species', 'breed', 'sex', 'birth_date',
        'mother_tag', 'father_tag', 'status', 'current_weight_kg', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'current_weight_kg' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'station_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnimalEvent::class, 'animal_id')->orderByDesc('event_date');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(AnimalHealthRecord::class, 'animal_id')->orderByDesc('record_date');
    }
}
