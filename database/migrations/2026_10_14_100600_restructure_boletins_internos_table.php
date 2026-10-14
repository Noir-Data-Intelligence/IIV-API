<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEF-002: o Boletim Interno (BI) passa a ser 1 por par (requisição × área)
 * com N amostras (pivot `boletim_interno_amostras`) — decisão D.0#1, a
 * confirmar com o laboratório. `amostra_id` perde o UNIQUE e fica nullable
 * (@deprecated, só dados antigos). Acrescenta os carimbos de cada
 * transição física do BI (encaminhamento, recepção na área, devolução,
 * recepção na CGISC).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('boletins_internos', ['amostra_id'], 'unique')) {
            Schema::table('boletins_internos', function (Blueprint $table) {
                $table->dropUnique(['amostra_id']);
            });
        }

        Schema::table('boletins_internos', function (Blueprint $table) {
            $table->uuid('amostra_id')->nullable()->change();
            $table->string('status')->default('gerado')->change();
        });

        Schema::table('boletins_internos', function (Blueprint $table) {
            $table->index('amostra_id');
            $table->foreignUuid('requisicao_id')->nullable()->constrained('requisicoes')->cascadeOnDelete();
            $table->foreignUuid('laboratorio_id')->nullable()->constrained('laboratorios');
            $table->string('numero')->nullable()->unique(); // PAT-###-ANO/AB
            $table->timestamp('prazo_em')->nullable();
            $table->string('resultado_global')->nullable(); // positivo|negativo|inconclusivo
            $table->foreignUuid('encaminhado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('encaminhado_em')->nullable();
            $table->foreignUuid('recebido_area_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recebido_area_em')->nullable();
            $table->foreignUuid('responsavel_tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('devolvido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('devolvido_em')->nullable();
            $table->foreignUuid('recebido_cgisc_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recebido_cgisc_em')->nullable();
            $table->text('motivo_correccao')->nullable();
            $table->foreignUuid('bi_origem_id')->nullable()->constrained('boletins_internos')->nullOnDelete();

            $table->index('status');
        });

        Schema::create('boletim_interno_amostras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('boletim_interno_id')->constrained('boletins_internos')->cascadeOnDelete();
            $table->foreignUuid('amostra_id')->constrained('amostras')->cascadeOnDelete();
            // pendente|em_ensaio|concluida|rejeitada_na_area
            $table->string('estado')->default('pendente');
            $table->timestamps();

            $table->unique(['boletim_interno_id', 'amostra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletim_interno_amostras');

        Schema::table('boletins_internos', function (Blueprint $table) {
            foreach (['requisicao_id', 'laboratorio_id', 'encaminhado_por_id', 'recebido_area_por_id', 'responsavel_tecnico_id', 'devolvido_por_id', 'recebido_cgisc_por_id', 'bi_origem_id'] as $fk) {
                $table->dropForeign([$fk]);
            }
            $table->dropUnique(['numero']);
            $table->dropIndex(['status']);
            $table->dropIndex(['amostra_id']);
            $table->dropColumn([
                'requisicao_id', 'laboratorio_id', 'numero', 'prazo_em', 'resultado_global',
                'encaminhado_por_id', 'encaminhado_em', 'recebido_area_por_id', 'recebido_area_em',
                'responsavel_tecnico_id', 'devolvido_por_id', 'devolvido_em', 'recebido_cgisc_por_id',
                'recebido_cgisc_em', 'motivo_correccao', 'bi_origem_id',
            ]);
        });
    }
};
