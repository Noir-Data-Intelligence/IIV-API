<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `current_version_id` sem FK real (referencia `doc_versions`, criada depois
 * desta tabela — dependência circular documento<->versão, mesmo padrão já
 * usado para boletim_interno/boletim_analise na Onda 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('category_id')->nullable()->constrained('doc_categories');
            $table->foreignUuid('owner_id')->constrained('users');
            $table->string('status')->default('rascunho');
            $table->string('visibility')->default('publico');
            $table->date('expiry_date')->nullable();
            // Sem `->default('[]')` a nível de BD: MySQL não aceita valor por
            // omissão literal em colunas JSON (`SQLSTATE[42000] 1101`) —
            // omissão fica antes a cargo do modelo (`Documento::$attributes`).
            $table->json('tags');
            $table->uuid('current_version_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
