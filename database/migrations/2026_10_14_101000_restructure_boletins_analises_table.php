<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEF-003: `boletins_analises` passa a ser o Boletim de Análises OFICIAL,
 * emitido só pela CGISC a partir de um BI recebido de volta. Os campos
 * antigos (`resultado`, `validador_*`, `aprovado_*`, `comunicado_em`) ficam
 * para compatibilidade com dados existentes (@deprecated no Model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletins_analises', function (Blueprint $table) {
            $table->string('numero')->nullable()->unique();
            $table->foreignUuid('requisicao_id')->nullable()->constrained('requisicoes')->cascadeOnDelete();
            $table->foreignUuid('laboratorio_id')->nullable()->constrained('laboratorios');
            $table->json('conteudo')->nullable();
            $table->foreignUuid('emitido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('emitido_em')->nullable();
            $table->foreignUuid('assinado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assinatura_hash')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->string('entregue_a')->nullable();
            $table->string('canal_entrega')->nullable(); // presencial|email|isv|portal
            $table->timestamp('isv_notificado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('boletins_analises', function (Blueprint $table) {
            foreach (['requisicao_id', 'laboratorio_id', 'emitido_por_id', 'assinado_por_id'] as $fk) {
                $table->dropForeign([$fk]);
            }
            $table->dropUnique(['numero']);
            $table->dropColumn([
                'numero', 'requisicao_id', 'laboratorio_id', 'conteudo', 'emitido_por_id', 'emitido_em',
                'assinado_por_id', 'assinatura_hash', 'pdf_path', 'entregue_em', 'entregue_a',
                'canal_entrega', 'isv_notificado_em',
            ]);
        });
    }
};
