<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onda 11 (Observatório Veterinário Nacional, Fase 1 — RDA §8.4): estrutura
 * as coordenadas de colheita como colunas numéricas reais, satisfazendo
 * RF-067 ("registar as coordenadas geográficas das colheitas"). Campo
 * aditivo e nullable — `ponto_colheita` (texto livre, já no contrato real
 * do frontend desde a Onda 3) mantém-se intacto, nunca substituído.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amostras', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('ponto_colheita');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('amostras', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
