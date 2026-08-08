<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabeçalho de intake (FM-SQ-040/041/042/046/047, FM-CGISC-073) — 1 requisição
 * pode conter N amostras (`amostras`), replicando o anexo de lote dos
 * FM-SQ-041/046.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisicoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios');
            $table->string('tipo_sujeito');
            $table->string('cliente_nome');
            $table->string('cliente_contacto')->nullable();
            $table->string('veterinario_responsavel')->nullable();
            $table->json('dados_epidemiologicos')->nullable();
            $table->json('dados_facturacao')->nullable();
            $table->boolean('consentimento')->default(false);
            $table->string('assinatura_cliente')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisicoes');
    }
};
