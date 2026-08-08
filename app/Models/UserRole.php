<?php

namespace App\Models;

use App\Enums\AppRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'user_id', 'role'];

    protected function casts(): array
    {
        return [
            'role' => AppRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
