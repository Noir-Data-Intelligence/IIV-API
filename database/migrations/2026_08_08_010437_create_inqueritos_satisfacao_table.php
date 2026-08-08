<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inquérito de satisfação do cliente (FM-SQ-066), entidade desacoplada
 * disparada após a entrega do boletim de análises — `dimensoes` guarda a
 * escala Bom/Regular/Mau por dimensão de serviço avaliada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inqueritos_satisfacao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requisicao_id')->constrained('requisicoes');
            $table->json('dimensoes');
            $table->text('comentario')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inqueritos_satisfacao');
    }
};
