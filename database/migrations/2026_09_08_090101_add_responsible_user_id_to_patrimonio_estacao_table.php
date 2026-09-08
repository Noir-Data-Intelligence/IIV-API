<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria funcional (01/09/2026, secção 21 — Património): "Associar
 * Responsável ao Departamento". Mesma mudança de
 * `2026_09_08_090100_add_responsible_user_id_to_patrimonio_central_table`,
 * aplicada ao esquema dual de Estação (ver SIG-IIV-MEMORIA-PROJETO.md
 * secção 6 — os dois esquemas têm de evoluir em paralelo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->foreignUuid('responsible_user_id')->nullable()->after('department_id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->dropColumn('responsible_user');
        });
    }

    public function down(): void
    {
        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->string('responsible_user')->nullable()->after('department_id');
        });

        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_user_id');
        });
    }
};
