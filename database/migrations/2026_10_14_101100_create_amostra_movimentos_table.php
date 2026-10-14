<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de rastreabilidade da amostra: cada movimento físico/lógico
 * (recepção, conferência, encaminhamento, ensaio, validação, devolução,
 * emissão, entrega...) com técnico, local e data/hora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amostra_movimentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('amostra_id')->constrained('amostras')->cascadeOnDelete();
            $table->foreignUuid('boletim_interno_id')->nullable()->constrained('boletins_internos')->nullOnDelete();
            $table->string('accao');
            $table->string('de_local')->nullable();
            $table->string('para_local')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['amostra_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amostra_movimentos');
    }
};
