<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onda 11 (Observatório Veterinário Nacional, Fase 1 — RDA §8.2
 * "Mapeamento das Estações Zootécnicas e Laboratórios: cadastro
 * georreferenciado de todas as unidades operacionais a nível nacional").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estacoes', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('estacoes', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
