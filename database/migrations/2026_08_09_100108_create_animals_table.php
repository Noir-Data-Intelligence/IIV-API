<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Animais (Onda 9) — efectivo animal individual por estação. `motherTag`/
 * `fatherTag` são strings livres (ligação genealógica por tag textual, não
 * FK — fiel ao `AnimalDto` real).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('station_id')->constrained('estacoes')->restrictOnDelete();
            $table->string('tag')->default('SEM-TAG');
            $table->string('name')->nullable();
            $table->string('species');
            $table->string('breed')->nullable();
            $table->string('sex')->default('femea');
            $table->date('birth_date')->nullable();
            $table->string('mother_tag')->nullable();
            $table->string('father_tag')->nullable();
            $table->string('status')->default('activo');
            $table->decimal('current_weight_kg', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
