<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Séries numéricas anuais (PAT, requisição, BA, termo de entrega) com
 * incremento atómico sob lock — substitui o antigo `count()+1` do
 * LabWorkflowService::gerarNumeroPat(), que gerava PATs duplicados entre a
 * série da requisição e a do BI e colidia em concorrência.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequencias', function (Blueprint $table) {
            $table->id();
            $table->string('chave');
            $table->unsignedSmallInteger('ano');
            $table->unsignedInteger('ultimo')->default(0);
            $table->timestamps();

            $table->unique(['chave', 'ano']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequencias');
    }
};
