<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\ProcessTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessType extends Model
{
    /** @use HasFactory<ProcessTypeFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $fillable = ['id', 'name', 'description', 'icon', 'sla_days', 'is_active', 'created_at'];

    protected function casts(): array
    {
        return [
            'sla_days' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessTypeStep::class)->orderBy('order_index');
    }
}
