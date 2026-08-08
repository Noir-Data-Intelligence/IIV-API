<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\DistribuicaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribuicao extends Model
{
    /** @use HasFactory<DistribuicaoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    public $timestamps = false;

    protected $table = 'distribuicao';

    protected $fillable = ['id', 'batch_id', 'destination', 'quantity', 'distribution_date', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'distribution_date' => 'date',
        ];
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'batch_id');
    }
}
