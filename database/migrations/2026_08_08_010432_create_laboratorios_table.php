<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo das 8 áreas laboratoriais + Sala de Incubação (REQ-005 §8,
 * SIG-IIV-MEMORIA-PROJETO.md secção 7). `validador_count` e
 * `validadores_nomeados` alimentam o LabWorkflowService (RN "validação
 * dupla/tripla" e "Bacteriologia — 3 validadores nomeados").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratorios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('validador_count')->default(2);
            $table->unsignedInteger('sla_horas')->nullable();
            $table->json('validadores_nomeados')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratorios');
    }
};
