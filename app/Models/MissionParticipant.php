<?php

namespace App\Models;

use Database\Factories\MissionParticipantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionParticipant extends Model
{
    /** @use HasFactory<MissionParticipantFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'mission_id', 'user_id', 'role', 'per_diem', 'created_at'];

    protected function casts(): array
    {
        return [
            'per_diem' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Missao::class, 'mission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
