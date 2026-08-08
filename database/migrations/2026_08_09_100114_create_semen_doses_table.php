<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Doses de sémen (Onda 9) — rastreáveis por reprodutor, tanque e validade. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semen_doses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('breeder_id')->constrained('breeders')->restrictOnDelete();
            $table->foreignUuid('tank_id')->nullable()->constrained('nitrogen_tanks')->nullOnDelete();
            $table->date('collection_date');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('available_quantity');
            $table->string('quality_grade')->default('A');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semen_doses');
    }
};
