<?php

namespace App\Models;

use Database\Factories\BreederFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Breeder extends Model
{
    /** @use HasFactory<BreederFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'center_id', 'tag', 'name', 'species', 'breed', 'birth_date', 'status', 'notes', 'created_at'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(IaCenter::class, 'center_id');
    }

    public function doses(): HasMany
    {
        return $this->hasMany(SemenDose::class, 'breeder_id');
    }
}
