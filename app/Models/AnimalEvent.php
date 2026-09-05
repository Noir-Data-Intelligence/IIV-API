<?php

namespace App\Models;

use Database\Factories\AnimalEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalEvent extends Model
{
    /** @use HasFactory<AnimalEventFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'animal_id', 'event_type', 'event_date', 'destination', 'notes', 'created_at'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}
