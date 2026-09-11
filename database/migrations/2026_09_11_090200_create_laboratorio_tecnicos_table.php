<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido do cliente: "o sistema deve prever a gestão de equipas (Técnicos)".
 * Análoga a `user_departments`, mas ligada a `Laboratorio` (que não tinha,
 * até agora, nenhuma relação com `users`). Distinta de
 * `Laboratorio.validadores_nomeados` (subconjunto autorizado a validar) —
 * esta tabela é a equipa operacional completa do laboratório.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratorio_tecnicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('funcao')->nullable();
            $table->timestamps();

            $table->unique(['laboratorio_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratorio_tecnicos');
    }
};
