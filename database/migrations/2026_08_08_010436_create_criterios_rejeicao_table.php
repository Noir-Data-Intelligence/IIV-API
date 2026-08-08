<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de motivos de rejeição (FM-SQ-061 geral, FM-SQ-075 alimentar).
 * `laboratorio_id` nulo = motivo geral, aplicável a qualquer área; motivos
 * específicos (ex.: alimentar) referenciam a área.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterios_rejeicao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->nullable()->constrained('laboratorios');
            $table->string('codigo');
            $table->string('motivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_rejeicao');
    }
};
