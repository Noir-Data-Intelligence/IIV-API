<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Reprodutores de um centro de IA (Onda 9). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('center_id')->constrained('ia_centers')->restrictOnDelete();
            $table->string('tag')->default('SEM-TAG');
            $table->string('name')->nullable();
            $table->string('species');
            $table->string('breed')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('status')->default('activo');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeders');
    }
};
