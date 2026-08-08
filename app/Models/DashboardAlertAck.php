<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DashboardAlertAck extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'user_id', 'metric_key', 'acknowledged_until'];

    protected function casts(): array
    {
        return ['acknowledged_until' => 'datetime'];
    }
}
