<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria funcional (01/09/2026, secção 21 — Património): "Associar
 * Responsável ao Departamento". `responsible_user` (string livre) não tinha
 * nenhuma ligação real ao sistema de utilizadores/departamentos — substitui-se
 * por `responsible_user_id`, FK para `users`, seguindo o mesmo padrão já usado
 * em `ia_centers.responsible_user_id` (Onda 9). O Departamento apresentado
 * passa a poder ser derivado do utilizador escolhido (`department_id`,
 * já existente nesta tabela, continua editável/independente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrimonio_central', function (Blueprint $table) {
            $table->foreignUuid('responsible_user_id')->nullable()->after('department_id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('patrimonio_central', function (Blueprint $table) {
            $table->dropColumn('responsible_user');
        });
    }

    public function down(): void
    {
        Schema::table('patrimonio_central', function (Blueprint $table) {
            $table->string('responsible_user')->nullable()->after('department_id');
        });

        Schema::table('patrimonio_central', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_user_id');
        });
    }
};
