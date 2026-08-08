<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Despesas de uma missão (Onda 8). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('missoes')->cascadeOnDelete();
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency')->default('AOA');
            $table->date('expense_date');
            $table->string('receipt_url')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_expenses');
    }
};
