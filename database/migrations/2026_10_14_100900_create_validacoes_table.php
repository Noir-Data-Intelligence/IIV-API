<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validações do BI (substitui os campos soltos validador_1/2/3_id de
 * `boletins_analises`). Unique (BI, validador): o mesmo utilizador nunca
 * valida duas vezes o mesmo BI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('boletim_interno_id')->constrained('boletins_internos')->cascadeOnDelete();
            $table->foreignUuid('validador_id')->constrained('users');
            $table->unsignedTinyInteger('ordem');
            $table->string('tipo'); // segundo|terceiro|hierarquica|qualidade
            $table->string('assinatura')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['boletim_interno_id', 'validador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validacoes');
    }
};
