<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: um utilizador pode ter >1 papel (ver App\Enums\AppRole — 12
     * valores fechados). `role` fica como string (nao FK) porque nao existe
     * tabela `roles` propria — a lista fechada e o enum, tal como no
     * frontend (`AppRole` em permissions.ts). `GET /user` devolve `roles`
     * ja consolidado (eager-load) — ver App\Http\Resources\UserResource.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
