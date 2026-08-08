<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legislacao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('num');
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('tipo')->default('Lei');
            $table->string('ano');
            $table->string('pdf_url')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legislacao');
    }
};
