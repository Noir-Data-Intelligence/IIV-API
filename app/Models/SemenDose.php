<?php

namespace App\Models;

use Database\Factories\SemenDoseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemenDose extends Model
{
    /** @use HasFactory<SemenDoseFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'breeder_id', 'tank_id', 'collection_date', 'quantity',
        'available_quantity', 'quality_grade', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'quantity' => 'integer',
            'available_quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class, 'breeder_id');
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(NitrogenTank::class, 'tank_id');
    }
}
