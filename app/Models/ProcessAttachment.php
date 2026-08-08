<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessAttachment extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'process_id', 'label', 'file_path', 'document_id', 'uploaded_by', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'process_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'document_id');
    }
}
