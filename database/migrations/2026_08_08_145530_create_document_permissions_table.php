<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Permissão granular dirigida a um papel OU a um departamento — sem timestamps, `DocumentPermissionDto` não os tem. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documentos');
            $table->string('role')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->boolean('can_edit')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};
