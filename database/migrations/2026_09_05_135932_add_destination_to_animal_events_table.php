<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campo "destino" pedido pela auditoria funcional para o evento de
 * transferência (o formulário só tinha tipo/data/notas, sem forma
 * estruturada de registar para onde o animal foi transferido).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_events', function (Blueprint $table) {
            $table->string('destination')->nullable()->after('event_date');
        });
    }

    public function down(): void
    {
        Schema::table('animal_events', function (Blueprint $table) {
            $table->dropColumn('destination');
        });
    }
};
