<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RH laboratorial — colaboradores (Onda 8). Esquema separado de
 * `rh_transversal_colaboradores` — ver nota de dualidade na migration irmã.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_laboratorio_colaboradores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_number')->unique();
            $table->string('full_name');
            $table->string('national_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('qualifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_laboratorio_colaboradores');
    }
};
