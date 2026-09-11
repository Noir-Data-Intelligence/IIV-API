<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido do cliente: "total autonomia de configuração de alertas (SLA)
 * para cada componente de forma independente, em vez de aplicar uma regra
 * rígida e unificada". `sla_horas` já existia mas nunca era lido por
 * nenhum job/serviço; estes campos tornam-no configurável por tipo de
 * regra (SIG-IIV-MEMORIA-PROJETO.md §7: AB tem prazos diferenciados
 * negativo/positivo, AMA usa dias em vez de horas, BR usa um gate de
 * qualidade em vez de prazo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->string('sla_rule_type')->nullable()->after('sla_horas');
            $table->integer('sla_horas_negativo')->nullable()->after('sla_rule_type');
            $table->integer('sla_dias')->nullable()->after('sla_horas_negativo');
            $table->boolean('sla_active')->default(true)->after('sla_dias');
            $table->string('sla_severity')->default('warning')->after('sla_active');
        });
    }

    public function down(): void
    {
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->dropColumn(['sla_rule_type', 'sla_horas_negativo', 'sla_dias', 'sla_active', 'sla_severity']);
        });
    }
};
