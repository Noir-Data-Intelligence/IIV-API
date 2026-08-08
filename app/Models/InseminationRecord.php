<?php

namespace App\Models;

use Database\Factories\InseminationRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InseminationRecord extends Model
{
    /** @use HasFactory<InseminationRecordFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'animal_id', 'dose_id', 'technician_id', 'insemination_date', 'result',
        'pregnancy_confirmed_at', 'expected_birth_date', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'insemination_date' => 'date',
            'pregnancy_confirmed_at' => 'date',
            'expected_birth_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function dose(): BelongsTo
    {
        return $this->belongsTo(SemenDose::class, 'dose_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
