<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boletim de Análises (BA) — resultado final emitido ao cliente. Os 3
 * campos de validador + `validado_em` implementam a RN "validação
 * dupla/tripla". A distinção entre validadores (2º ≠ 1º, 3º ≠ 1º/2º) e o
 * número exigido por disciplina (incl. a lista nomeada da Bacteriologia,
 * ver `laboratorios.validadores_nomeados`) são impostos pelo
 * LabWorkflowService — não há CHECK de BD porque o driver alvo
 * (Blueprint desta versão do Laravel) não suporta `check()` de forma
 * portátil entre SQLite (dev) e PostgreSQL (produção).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletins_analises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('boletim_interno_id')->unique()->constrained('boletins_internos');
            $table->json('resultado')->nullable();
            $table->foreignUuid('validador_1_id')->nullable()->constrained('users');
            $table->foreignUuid('validador_2_id')->nullable()->constrained('users');
            $table->foreignUuid('validador_3_id')->nullable()->constrained('users');
            $table->timestamp('validado_em')->nullable();
            $table->foreignUuid('aprovado_por_id')->nullable()->constrained('users');
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamp('comunicado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletins_analises');
    }
};
