<?php

namespace App\Models;

use Database\Factories\CropFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    /** @use HasFactory<CropFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'name', 'scientific_name', 'cycle_days', 'notes', 'created_at'];

    protected function casts(): array
    {
        return [
            'cycle_days' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'crop_id');
    }
}
