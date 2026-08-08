<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `sujeito` guarda os dados do animal (espécie/sexo/idade/raça/ID) ou do
 * paciente humano (nome/idade/sexo) num único JSON — os pares de formulários
 * animal/humano (FM-SQ-041 vs FM-SQ-046) partilham a mesma estrutura de
 * amostra, só o "sujeito" muda de forma (RN "sujeito polimórfico").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amostras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requisicao_id')->constrained('requisicoes');
            $table->string('numero')->unique();
            $table->string('tipo_amostra');
            $table->string('origem_matriz')->nullable();
            $table->string('ponto_colheita')->nullable();
            $table->string('colhida_por')->nullable();
            $table->json('sujeito')->nullable();
            $table->timestamp('recebida_em');
            $table->string('status')->default('recebida');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amostras');
    }
};
