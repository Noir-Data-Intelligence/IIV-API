<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria funcional (01/09/2026, secção 21 — Património): "Tornar
 * Descrição obrigatória" em Património de Estação — ao contrário de
 * Património Central, cujo `description` continua opcional (o documento
 * distingue deliberadamente os dois módulos). Faz-se o backfill de
 * eventuais linhas sem descrição antes de aplicar o `NOT NULL`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('patrimonio_estacao')->whereNull('description')->update(['description' => '']);

        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('patrimonio_estacao', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};
