<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\EstacaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estacao extends Model
{
    /** @use HasFactory<EstacaoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $table = 'estacoes';

    public $timestamps = false;

    protected $fillable = [
        'id', 'name', 'station_type', 'location', 'latitude', 'longitude',
        'description', 'is_active', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'station_id');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'station_id');
    }

    public function producaoPecuaria(): HasMany
    {
        return $this->hasMany(ProducaoPecuaria::class, 'station_id');
    }
}
