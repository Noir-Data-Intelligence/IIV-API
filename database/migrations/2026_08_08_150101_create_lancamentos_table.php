<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Movimentos financeiros (Onda 7 — Financeiro). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('contas')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->string('currency')->default('AOA');
            $table->date('transaction_date');
            $table->string('description');
            $table->string('reference')->nullable();
            $table->string('status')->default('pendente');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
