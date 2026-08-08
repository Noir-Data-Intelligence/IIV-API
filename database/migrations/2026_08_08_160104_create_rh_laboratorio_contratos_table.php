<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RH laboratorial — contratos (Onda 8). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_laboratorio_contratos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('rh_laboratorio_colaboradores')->cascadeOnDelete();
            $table->string('contract_type');
            $table->string('position');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 14, 2);
            $table->string('currency')->default('AOA');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_laboratorio_contratos');
    }
};
