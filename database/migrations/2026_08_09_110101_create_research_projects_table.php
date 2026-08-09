<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projectos de I&D (Onda 10). `line_id` usa `nullOnDelete()` — apagar uma
 * linha de investigação desassocia (não arrasta) os seus projectos, fiel a
 * `mocks/handlers/investigacao.ts` (`p.lineId = null`, não `CASCADE`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('line_id')->nullable()->constrained('research_lines')->nullOnDelete();
            $table->string('title');
            $table->text('objectives')->nullable();
            $table->string('status')->default('proposto');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('funding_source')->nullable();
            $table->decimal('funding_amount', 14, 2)->nullable();
            $table->string('currency')->default('AOA');
            $table->text('partners')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_projects');
    }
};
