<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Não-conformidades — Onda 5, referencia opcionalmente uma auditoria de origem. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nao_conformidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->string('severity');
            $table->string('status')->default('aberta');
            $table->text('corrective_action')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->foreignUuid('audit_id')->nullable()->constrained('auditorias');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nao_conformidades');
    }
};
