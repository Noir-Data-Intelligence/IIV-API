<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distribuições de lotes — sem timestamps de propósito: `DistribuicaoDto`
 * (contrato frontend) não tem `createdAt`, ver types/dto/distribuicao.ts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribuicao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_id')->constrained('lotes');
            $table->string('destination');
            $table->unsignedInteger('quantity');
            $table->date('distribution_date');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribuicao');
    }
};
