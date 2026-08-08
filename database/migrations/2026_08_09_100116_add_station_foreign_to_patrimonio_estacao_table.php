<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FK retroactiva (Onda 9): `patrimonio_estacao.station_id` (Onda 7) era uma
 * coluna uuid solta porque `estacoes` ainda não existia — agora que existe,
 * liga-se a FK real. `restrictOnDelete()` — uma estação com património
 * associado não pode ser apagada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->foreign('station_id')->references('id')->on('estacoes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
        });
    }
};
