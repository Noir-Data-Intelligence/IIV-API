<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'process_id', 'actor_id', 'event_type', 'payload', 'created_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'process_id');
    }
}
