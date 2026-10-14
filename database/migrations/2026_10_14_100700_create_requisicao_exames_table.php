<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exames pedidos na requisição. `laboratorio_id` é denormalizado do exame
 * do catálogo e é a base para gerar 1 BI por área distinta ao abrir o
 * processo. `amostra_id` null = o exame aplica-se a todas as amostras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisicao_exames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requisicao_id')->constrained('requisicoes')->cascadeOnDelete();
            $table->foreignUuid('amostra_id')->nullable()->constrained('amostras')->cascadeOnDelete();
            $table->foreignUuid('exame_id')->constrained('exames_catalogo');
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios');
            $table->foreignUuid('boletim_interno_id')->nullable()->constrained('boletins_internos')->nullOnDelete();
            $table->timestamps();

            $table->index(['requisicao_id', 'laboratorio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisicao_exames');
    }
};
