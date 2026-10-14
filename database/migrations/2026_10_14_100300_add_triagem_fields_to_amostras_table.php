<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amostras', function (Blueprint $table) {
            $table->timestamp('aceite_em')->nullable();
            $table->boolean('aceite_com_reserva')->default(false);
            $table->text('reserva_motivo')->nullable();
            // Identificador impresso na etiqueta (PAT-###-ANO-A{n}) — atribuído ao abrir o processo.
            $table->string('codigo_barras')->nullable()->unique();
            $table->foreignUuid('amostra_origem_id')->nullable()->constrained('amostras')->nullOnDelete();
            $table->string('localizacao_actual')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('amostras', function (Blueprint $table) {
            $table->dropForeign(['amostra_origem_id']);
            $table->dropUnique(['codigo_barras']);
            $table->dropColumn(['aceite_em', 'aceite_com_reserva', 'reserva_motivo', 'codigo_barras', 'amostra_origem_id', 'localizacao_actual']);
        });
    }
};
