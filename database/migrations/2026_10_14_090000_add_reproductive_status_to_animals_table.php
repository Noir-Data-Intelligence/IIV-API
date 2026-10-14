<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campo "Estado Reprodutivo", exclusivo de fêmeas (CT-012 — relatório de QA,
 * "Aprovado c/ Observação": o brinco já era gerado correctamente, mas o
 * formulário nunca expunha este campo). Livre de FK — valores fechados são
 * validados em `AnimalRequest` (vazia, cio, coberta, gestante, lactante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('reproductive_status')->nullable()->after('sex');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('reproductive_status');
        });
    }
};
