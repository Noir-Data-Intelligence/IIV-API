<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recepção CGISC (DEF-001): `laboratorio_id` deixa de ser obrigatório —
 *
 * @deprecated mantido só para dados antigos; a(s) área(s) derivam agora de
 * `requisicao_exames`. Acrescenta o estado do processo, origem, Termo de
 * Entrega, PAT e encerramento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->uuid('laboratorio_id')->nullable()->change();
        });

        Schema::table('requisicoes', function (Blueprint $table) {
            $table->string('status')->default('registada');
            $table->string('origem_tipo')->nullable(); // publica|privada|singular
            $table->boolean('proveniencia_local')->default(true);
            $table->string('termo_entrega_numero')->nullable();
            $table->string('termo_entrega_assinado_por')->nullable();
            $table->string('entregue_por')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->foreignUuid('recebida_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('conservacao')->nullable();
            $table->timestamp('colheita_em')->nullable();
            $table->text('doencas_suspeitas')->nullable();
            $table->string('pat_numero')->nullable()->unique();
            $table->timestamp('processo_aberto_em')->nullable();
            $table->timestamp('encerrada_em')->nullable();
            $table->foreignUuid('encerrada_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('requisicoes', function (Blueprint $table) {
            $table->dropForeign(['recebida_por_id']);
            $table->dropForeign(['encerrada_por_id']);
            $table->dropIndex(['status']);
            $table->dropUnique(['pat_numero']);
            $table->dropColumn([
                'status', 'origem_tipo', 'proveniencia_local', 'termo_entrega_numero',
                'termo_entrega_assinado_por', 'entregue_por', 'entregue_em', 'recebida_por_id',
                'conservacao', 'colheita_em', 'doencas_suspeitas', 'pat_numero',
                'processo_aberto_em', 'encerrada_em', 'encerrada_por_id',
            ]);
        });
    }
};
