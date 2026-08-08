<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Histórico de manutenções do património central (Onda 7). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonio_central_manutencoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patrimonio_central_id')->constrained('patrimonio_central')->cascadeOnDelete();
            $table->date('date');
            $table->string('type');
            $table->text('description');
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('provider')->nullable();
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimonio_central_manutencoes');
    }
};
