<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Missões (Onda 8) — 3ª máquina de estados do sistema (a 1ª é o
 * LabWorkflowService da Onda 3, a 2ª o ProcessWorkflowService da Onda 6):
 * planeada -> submetida -> aprovada -> em_curso -> concluida | cancelada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('destination');
            $table->text('purpose')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planeada');
            $table->decimal('budget', 14, 2)->default(0);
            $table->string('currency')->default('AOA');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missoes');
    }
};
