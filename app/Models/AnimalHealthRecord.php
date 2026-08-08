<?php

namespace App\Models;

use Database\Factories\AnimalHealthRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalHealthRecord extends Model
{
    /** @use HasFactory<AnimalHealthRecordFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'animal_id', 'record_type', 'product_name', 'dosage', 'diagnosis',
        'treatment', 'veterinarian', 'record_date', 'next_due_date', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'next_due_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}
