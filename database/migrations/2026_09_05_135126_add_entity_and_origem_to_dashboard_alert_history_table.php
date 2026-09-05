<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suporta alertas gerados pelo servidor (comando agendado), sem utilizador
 * autenticado a criá-los: `user_id` passa a opcional, e adiciona-se
 * `entity_type`/`entity_id` (o "registo associado" pedido pela auditoria
 * funcional — Histórico de Alertas) e `origem` (browser|sistema).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_alert_history', function (Blueprint $table) {
            // `uuid()` (não `foreignUuid()`) para alterar só o tipo/nulidade
            // da coluna sem tocar na constraint FK já existente.
            $table->uuid('user_id')->nullable()->change();
            $table->string('entity_type')->nullable()->after('tone');
            $table->uuid('entity_id')->nullable()->after('entity_type');
            $table->string('origem')->default('browser')->after('entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_alert_history', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'entity_id', 'origem']);
            $table->uuid('user_id')->nullable(false)->change();
        });
    }
};
