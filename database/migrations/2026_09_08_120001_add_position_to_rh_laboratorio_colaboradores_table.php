<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campo "Cargo" pedido pela auditoria funcional (secção 22, RH Laboratorial):
 * já existia em `rh_laboratorio_contratos.position` (vínculo contratual),
 * mas faltava na ficha do colaborador — ver nota de dualidade na migration
 * irmã (`add_position_to_rh_transversal_colaboradores_table`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_laboratorio_colaboradores', function (Blueprint $table) {
            $table->string('position')->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('rh_laboratorio_colaboradores', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
