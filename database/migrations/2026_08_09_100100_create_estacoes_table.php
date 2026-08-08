<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estações zootécnicas (Onda 9). Primeira vez que esta entidade existe como
 * tabela real — `patrimonio_estacao.station_id` (Onda 7) e o `stationId` das
 * entidades desta Onda referem-na. Ver migration
 * `add_station_foreign_to_patrimonio_estacao` para a FK retroactiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('station_type')->default('zootecnica');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estacoes');
    }
};
