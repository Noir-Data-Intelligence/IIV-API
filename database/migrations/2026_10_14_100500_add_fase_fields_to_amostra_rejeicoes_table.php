<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Termo de Rejeição com 2 assinaturas e fase (recepcao|area). O UNIQUE de
 * `amostra_id` é removido: a mesma amostra pode ser rejeitada numa área e
 * seguir normalmente noutra (1 BI por área).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('amostra_rejeicoes', ['amostra_id'], 'unique')) {
            Schema::table('amostra_rejeicoes', function (Blueprint $table) {
                $table->dropUnique(['amostra_id']);
            });
        }

        Schema::table('amostra_rejeicoes', function (Blueprint $table) {
            $table->index('amostra_id');
            $table->foreignUuid('laboratorio_id')->nullable()->constrained('laboratorios')->nullOnDelete();
            $table->string('fase')->default('recepcao'); // recepcao|area
            $table->foreignUuid('boletim_interno_id')->nullable()->constrained('boletins_internos')->nullOnDelete();
            $table->foreignUuid('segundo_responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assinatura_segundo')->nullable();
            $table->timestamp('origem_notificada_em')->nullable();
            $table->text('accao_tomada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('amostra_rejeicoes', function (Blueprint $table) {
            $table->dropForeign(['laboratorio_id']);
            $table->dropForeign(['boletim_interno_id']);
            $table->dropForeign(['segundo_responsavel_id']);
            $table->dropIndex(['amostra_id']);
            $table->dropColumn(['laboratorio_id', 'fase', 'boletim_interno_id', 'segundo_responsavel_id', 'assinatura_segundo', 'origem_notificada_em', 'accao_tomada']);
        });
    }
};
