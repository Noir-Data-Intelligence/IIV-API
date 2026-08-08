<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overrides de permissao por (papel, modulo) — alimenta
     * PermissionMatrixService::forRole(), equivalente a `ROLE_PERMISSIONS`
     * + `setDynamicPermissions()` em Front-end/src/lib/permissions.ts. Seed
     * inicial = copia exacta da matriz estatica do frontend (ver
     * RolePermissionSeeder).
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role');
            $table->string('module');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_write')->default(false);
            $table->timestamps();

            $table->unique(['role', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
