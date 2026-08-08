<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Termo de Rejeição (RN "Termo de Rejeição obrigatório": área + motivo +
 * data + assinatura, ou bloqueia encerramento) — `assinatura_responsavel`
 * é NOT NULL de propósito, não apenas validado na camada de serviço.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amostra_rejeicoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('amostra_id')->unique()->constrained('amostras');
            $table->foreignUuid('criterio_rejeicao_id')->constrained('criterios_rejeicao');
            $table->foreignUuid('responsavel_id')->constrained('users');
            $table->text('detalhe')->nullable();
            $table->string('assinatura_responsavel');
            $table->timestamp('rejeitada_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amostra_rejeicoes');
    }
};
