<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Colheitas (Onda 9) — apagar um campo arrasta as suas colheitas. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('field_id')->constrained('fields')->cascadeOnDelete();
            $table->date('harvest_date');
            $table->decimal('quantity', 14, 2);
            $table->string('unit')->default('kg');
            $table->string('quality_grade')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
