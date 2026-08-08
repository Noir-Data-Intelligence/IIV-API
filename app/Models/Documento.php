<?php

namespace App\Models;

use App\Concerns\LogsAuditTrail;
use Database\Factories\DocumentoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documento extends Model
{
    /** @use HasFactory<DocumentoFactory> */
    use HasFactory, HasUuids, LogsAuditTrail;

    protected $fillable = [
        'id', 'title', 'description', 'category_id', 'owner_id', 'status',
        'visibility', 'expiry_date', 'tags', 'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'tags' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocVersion::class, 'document_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class, 'document_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class, 'document_id');
    }
}
