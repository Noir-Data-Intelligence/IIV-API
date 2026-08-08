<?php

namespace App\Models;

use App\Enums\AppRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'role', 'module', 'can_view', 'can_write'];

    protected function casts(): array
    {
        return [
            'role' => AppRole::class,
            'can_view' => 'boolean',
            'can_write' => 'boolean',
        ];
    }
}
