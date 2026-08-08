<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boletim Interno (BI) — único documento que circula entre a Recepção e a
 * área (RN "Boletim Interno é o único documento que circula"); 1:1 com a
 * amostra, só existe depois da triagem aceitar a amostra (ver
 * LabWorkflowService::aceitarAmostra()). `exames` é o checklist específico
 * da disciplina (decisão de desenho: JSON validado por disciplina em vez de
 * 8 tabelas filhas quase-duplicadas — ver SIG-IIV-MEMORIA-PROJETO.md §15).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletins_internos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('amostra_id')->unique()->constrained('amostras');
            $table->string('numero_analise')->unique();
            $table->json('exames')->nullable();
            $table->timestamp('entrada_em');
            $table->timestamp('inicio_em')->nullable();
            $table->timestamp('conclusao_em')->nullable();
            $table->string('status')->default('em_analise');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletins_internos');
    }
};
