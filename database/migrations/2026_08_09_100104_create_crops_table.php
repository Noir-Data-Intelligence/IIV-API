<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Culturas agrícolas (Onda 9). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('scientific_name')->nullable();
            $table->unsignedInteger('cycle_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
