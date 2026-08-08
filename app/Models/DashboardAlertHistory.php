<?php

namespace App\Models;

use Database\Factories\DashboardAlertHistoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardAlertHistory extends Model
{
    /** @use HasFactory<DashboardAlertHistoryFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'dashboard_alert_history';

    protected $fillable = [
        'id', 'metric_key', 'label', 'value', 'threshold', 'tone', 'user_id',
        'assigned_to', 'assigned_department_id', 'action_status', 'action_notes',
        'assigned_at', 'resolved_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'threshold' => 'float',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }
}
