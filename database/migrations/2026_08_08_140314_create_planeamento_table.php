<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planos de produção — sem timestamps de propósito: `PlanoDto` (contrato
 * frontend) não tem `createdAt`, ver types/dto/plano.ts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planeamento', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('produtos');
            $table->unsignedInteger('planned_quantity');
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->date('planned_start');
            $table->date('planned_end');
            $table->string('status')->default('planeada');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planeamento');
    }
};
