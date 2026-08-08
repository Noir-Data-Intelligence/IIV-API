<?php

namespace App\Models;

use Database\Factories\MissionGuideFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionGuide extends Model
{
    /** @use HasFactory<MissionGuideFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'mission_id', 'guide_number', 'issue_date', 'per_diem', 'transport', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'per_diem' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Missao::class, 'mission_id');
    }
}
