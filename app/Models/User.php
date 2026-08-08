<?php

namespace App\Models;

use App\Enums\AppRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'id',
        'full_name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'user_departments')
            ->using(UserDepartment::class)
            ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /** @return list<AppRole> */
    public function roleValues(): array
    {
        return $this->userRoles->pluck('role')->all();
    }

    /**
     * Papel principal (primeiro por ordem alfabetica) — usado pela
     * autorizacao do servidor (EnsureModulePermission) e para derivar a
     * password mock (`<papel>123`) nos seeders. Espelha `primaryRole()`
     * em Front-end/src/lib/permissions.ts.
     */
    public function primaryRole(): ?AppRole
    {
        return AppRole::primary($this->roleValues());
    }

    public function hasRole(AppRole $role): bool
    {
        return in_array($role, $this->roleValues(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(AppRole::Admin);
    }
}
