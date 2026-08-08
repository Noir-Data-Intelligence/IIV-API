<?php

namespace App\Models;

use Database\Factories\DocVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocVersion extends Model
{
    /** @use HasFactory<DocVersionFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'document_id', 'version_number', 'file_path', 'file_name',
        'file_size', 'mime_type', 'change_notes', 'uploaded_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'file_size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'document_id');
    }
}
