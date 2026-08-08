<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Produção pecuária (Onda 9) — leite/ovos/carne/mel por estação. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producao_pecuaria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('station_id')->constrained('estacoes')->restrictOnDelete();
            $table->string('product_type')->default('Outro');
            $table->date('production_date');
            $table->decimal('quantity', 14, 2);
            $table->string('unit')->default('kg');
            $table->string('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producao_pecuaria');
    }
};
