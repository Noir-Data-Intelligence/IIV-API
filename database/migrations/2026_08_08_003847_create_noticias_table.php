<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colunas em snake_case propositado — `NoticiaDto` (Front-end/src/types/dto/
     * noticia.ts) usa `image_path`/`published_at`/`created_at` tal qual, ao
     * contrário da maioria dos outros DTOs (camelCase). Ver nota no ficheiro.
     */
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->text('resumo')->nullable();
            $table->longText('conteudo')->nullable();
            $table->string('categoria')->default('Geral');
            $table->string('image_path')->nullable();
            $table->boolean('destaque')->default(false);
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
