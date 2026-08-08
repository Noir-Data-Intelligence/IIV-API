<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avaliações de desempenho (Onda 8) — 4ª máquina de estados do sistema:
 * rascunho -> submetida -> aprovada -> validada, com "rejeitar" (submetida ->
 * rejeitada) e "reabrir" (aprovada|validada|rejeitada -> rascunho) especiais.
 *
 * `employee_id` é propositadamente uma coluna solta sem `->constrained()`:
 * pode referir um colaborador de `rh_transversal_colaboradores` OU de
 * `rh_laboratorio_colaboradores` (dualidade obrigatória, ver secção 6 da
 * memória do projecto) — mesmo padrão de FK-circular-adiada já usado para
 * `station_id`/`current_step_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('evaluation_cycles')->cascadeOnDelete();
            $table->uuid('employee_id');
            $table->foreignUuid('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('evaluation_date')->nullable();
            $table->decimal('global_score', 5, 2)->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('general_comments')->nullable();
            $table->string('status')->default('rascunho');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
