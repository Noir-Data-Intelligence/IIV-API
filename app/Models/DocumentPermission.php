<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPermission extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['id', 'document_id', 'role', 'department_id', 'can_edit'];

    protected function casts(): array
    {
        return ['can_edit' => 'boolean'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'document_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
