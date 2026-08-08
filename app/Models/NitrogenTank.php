<?php

namespace App\Models;

use Database\Factories\NitrogenTankFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NitrogenTank extends Model
{
    /** @use HasFactory<NitrogenTankFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'center_id', 'code', 'capacity_l', 'current_level_l', 'min_level_l',
        'last_refill_date', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity_l' => 'decimal:2',
            'current_level_l' => 'decimal:2',
            'min_level_l' => 'decimal:2',
            'last_refill_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(IaCenter::class, 'center_id');
    }
}
