<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Orçamentos anuais por conta/departamento (Onda 7 — Financeiro). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('year');
            $table->foreignUuid('account_id')->constrained('contas')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->decimal('planned_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
