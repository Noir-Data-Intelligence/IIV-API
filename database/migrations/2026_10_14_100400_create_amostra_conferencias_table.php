<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dupla conferência na recepção (DEF-001): 6 critérios de conformidade e
 * confirmação obrigatória por um 2º técnico diferente do 1º (regra aplicada
 * em App\Services\Lab\RecepcaoService::conferir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amostra_conferencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('amostra_id')->constrained('amostras')->cascadeOnDelete();
            $table->foreignUuid('tecnico_id')->constrained('users');
            $table->unsignedTinyInteger('ordem');
            $table->boolean('crit_identificacao');
            $table->boolean('crit_quantidade');
            $table->boolean('crit_conservacao');
            $table->boolean('crit_macroscopica');
            $table->boolean('crit_caracteristicas_fisicas');
            $table->boolean('crit_tempo_colheita');
            $table->boolean('conforme');
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['amostra_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amostra_conferencias');
    }
};
