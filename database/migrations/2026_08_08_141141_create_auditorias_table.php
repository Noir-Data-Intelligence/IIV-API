<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Auditorias de qualidade — Onda 5. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('audit_type');
            $table->string('auditor');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->string('status')->default('planeada');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->foreignUuid('laboratory_id')->nullable()->constrained('laboratorios');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
