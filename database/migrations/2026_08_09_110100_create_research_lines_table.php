<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Linhas de investigação (Onda 10). Sem cobertura formal na REQ-005 (só
 * RF-096–098 "Estatística e Inteligência Institucional" cobre esta Onda) —
 * domínio herdado do produto pré-existente, replicado fielmente ao contrato
 * real do frontend (`research_lines`, ver `types/dto/investigacao.ts`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('area')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('activa');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_lines');
    }
};
