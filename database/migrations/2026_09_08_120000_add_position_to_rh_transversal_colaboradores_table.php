<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campo "Cargo" pedido pela auditoria funcional (secção 22, RH Transversal):
 * já existia em `rh_transversal_contratos.position` (vínculo contratual),
 * mas faltava na ficha do colaborador — necessário para mostrar/editar o
 * cargo actual sem depender de consultar o contrato activo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_transversal_colaboradores', function (Blueprint $table) {
            $table->string('position')->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('rh_transversal_colaboradores', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
