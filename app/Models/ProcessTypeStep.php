<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessTypeStep extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'process_type_id', 'order_index', 'name', 'default_role', 'sla_days'];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'sla_days' => 'integer',
        ];
    }

    public function processType(): BelongsTo
    {
        return $this->belongsTo(ProcessType::class);
    }
}
