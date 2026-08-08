<?php

namespace App\Models;

use Database\Factories\IaCenterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IaCenter extends Model
{
    /** @use HasFactory<IaCenterFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'name', 'location', 'responsible_user_id', 'notes', 'is_active', 'created_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function breeders(): HasMany
    {
        return $this->hasMany(Breeder::class, 'center_id');
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(NitrogenTank::class, 'center_id');
    }
}
