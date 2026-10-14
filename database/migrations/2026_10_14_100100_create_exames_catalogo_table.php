<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de exames por área laboratorial. A(s) área(s) de uma requisição
 * passam a ser DERIVADAS dos exames pedidos (DEF-001), em vez de escolhidas
 * num select à partida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exames_catalogo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios');
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->string('tecnica_metodo')->nullable();
            $table->string('pop_codigo')->nullable();
            $table->json('tipos_amostra')->nullable();
            $table->unsignedInteger('sla_horas')->nullable();
            // Schema dos parâmetros de resultado, ex: [{"chave":"salmonela","tipo":"numero","obrigatorio":true}]
            $table->json('campos_resultado')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exames_catalogo');
    }
};
