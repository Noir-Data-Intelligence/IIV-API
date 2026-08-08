<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ligação de um documento a uma entidade genérica do sistema (auditoria, lote, ...). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documentos');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
