<?php

namespace App\Models;

use Database\Factories\MissionReportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionReport extends Model
{
    /** @use HasFactory<MissionReportFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'mission_id', 'report_date', 'summary', 'outcomes', 'status',
        'submitted_by', 'approved_by', 'approved_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Missao::class, 'mission_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
