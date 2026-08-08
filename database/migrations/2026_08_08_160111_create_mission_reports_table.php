<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Relatório pós-missão (Onda 8) — 0..1 por missão (upsert). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->unique()->constrained('missoes')->cascadeOnDelete();
            $table->date('report_date');
            $table->text('summary')->nullable();
            $table->text('outcomes')->nullable();
            $table->string('status')->default('rascunho');
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_reports');
    }
};
