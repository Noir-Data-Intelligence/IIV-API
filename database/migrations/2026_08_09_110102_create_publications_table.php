<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Publicações científicas (Onda 10). `project_id` usa `nullOnDelete()` —
 * apagar um projecto desassocia (não arrasta) as suas publicações, fiel ao
 * mock. `authors` é um array JSON de strings (sem entidade `Investigador`
 * própria — o contrato real não a tem).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->nullable()->constrained('research_projects')->nullOnDelete();
            $table->string('type')->default('artigo');
            $table->string('title');
            $table->json('authors')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('venue')->nullable();
            $table->string('doi')->nullable();
            $table->string('url')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
