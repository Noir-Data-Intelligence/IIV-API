<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D.0#4/#5 (a confirmar com o laboratório): responsável de qualidade por
 * área (nullable, sem valores inventados nos dados de demonstração) e flag
 * configurável de pagamento obrigatório antes da entrega do BA (default
 * false — por omissão NÃO bloqueia a entrega).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->foreignUuid('responsavel_qualidade_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('exige_pagamento_para_entrega')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->dropForeign(['responsavel_qualidade_id']);
            $table->dropColumn(['responsavel_qualidade_id', 'exige_pagamento_para_entrega']);
        });
    }
};
