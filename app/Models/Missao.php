<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\MissaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Missao extends Model
{
    /** @use HasFactory<MissaoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'missoes';

    public $timestamps = false;

    protected $fillable = [
        'id', 'title', 'destination', 'purpose', 'start_date', 'end_date', 'status',
        'budget', 'currency', 'notes', 'created_by', 'approved_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MissionParticipant::class, 'mission_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(MissionExpense::class, 'mission_id')->orderByDesc('expense_date');
    }

    public function guide(): HasOne
    {
        return $this->hasOne(MissionGuide::class, 'mission_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(MissionReport::class, 'mission_id');
    }
}
