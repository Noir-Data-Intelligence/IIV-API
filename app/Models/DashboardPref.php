<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DashboardPref extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'user_id', 'kpis', 'charts', 'thresholds', 'updated_at'];

    protected function casts(): array
    {
        return [
            'kpis' => 'array',
            'charts' => 'array',
            'thresholds' => 'array',
            'updated_at' => 'datetime',
        ];
    }
}
