<?php

namespace App\Models;

use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publication extends Model
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'project_id', 'type', 'title', 'authors', 'year', 'venue', 'doi', 'url', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'year' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
