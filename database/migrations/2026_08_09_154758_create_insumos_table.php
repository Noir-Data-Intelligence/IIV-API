<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Insumos/reagentes laboratoriais — espelha `InsumoDto` em
     * Front-end/src/types/dto/insumo.ts. `quantity`/`min_stock` são
     * inteiros (o frontend valida `Number.isInteger`, não decimais como o
     * módulo Stock da Onda 9).
     */
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit');
            $table->unsignedInteger('min_stock')->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
