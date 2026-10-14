<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correcção CT-003 (QA Gestão de Processos, Reprovado): `process_steps`,
 * `process_events` e `process_attachments` ligavam-se a `processos` sem
 * `cascadeOnDelete()` — ao contrário do padrão usado no resto do projecto
 * para tabelas "filhas" (ver `mission_participants`, `evaluation_scores`,
 * `animal_events`, etc.). Resultado: apagar um processo com etapas/eventos
 * já instanciados (ou seja, qualquer processo criado via POST /processos)
 * rebentava com "FOREIGN KEY constraint failed" em vez de remover o registo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_steps', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
        });
        Schema::table('process_steps', function (Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processos')->cascadeOnDelete();
        });

        Schema::table('process_events', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
        });
        Schema::table('process_events', function (Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processos')->cascadeOnDelete();
        });

        Schema::table('process_attachments', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
        });
        Schema::table('process_attachments', function (Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('process_steps', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
        });
        Schema::table('process_steps', function (Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processos');
        });

        Schema::table('process_events', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
        });
        Schema::table('process_events', function (Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processos');
        });

        Schema::table('process_attachments', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
        });
        Schema::table('process_attachments', function (Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processos');
        });
    }
};
