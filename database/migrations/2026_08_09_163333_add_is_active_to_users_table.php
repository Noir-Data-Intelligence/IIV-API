<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Auditoria de segurança (2026-08-09): `POST /register` público criava a
     * conta E a sessão no mesmo pedido, dando a qualquer anónimo acesso de
     * leitura imediato (papel `recepcionista`) a requisições/amostras,
     * documentos e avaliações de desempenho. `is_active=false` por omissão
     * nas contas auto-registadas — só um admin (`PUT /users/{id}`) as activa.
     * Default `true` para não afectar contas já existentes (seed/admin).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('avatar_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
