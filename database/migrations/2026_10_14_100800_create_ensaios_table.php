<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boletim de ensaio (interno à área): 1 linha por (BI × amostra × exame),
 * com método/POP, equipamento, lote de reagente e executor — DEF-002.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ensaios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('boletim_interno_id')->constrained('boletins_internos')->cascadeOnDelete();
            $table->foreignUuid('amostra_id')->constrained('amostras')->cascadeOnDelete();
            $table->foreignUuid('exame_id')->constrained('exames_catalogo');
            $table->string('tecnica')->nullable();
            $table->string('pop_codigo')->nullable();
            // Sem FK: ainda não existe um registo de equipamentos de laboratório (a ligar ao Património).
            $table->string('equipamento_id')->nullable();
            $table->string('lote_reagente')->nullable();
            $table->foreignUuid('executor_id')->constrained('users');
            $table->foreignUuid('segundo_interveniente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('visualizador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->json('resultado')->nullable();
            $table->string('resultado_qualitativo')->nullable(); // positivo|negativo|inconclusivo
            $table->text('interpretacao')->nullable();
            $table->timestamps();

            $table->unique(['boletim_interno_id', 'amostra_id', 'exame_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ensaios');
    }
};
