<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Património da Direcção-Geral/Laboratório (Onda 7). Esquema deliberadamente
 * separado de `patrimonio_estacao` — dualidade obrigatória do projecto
 * (ver SIG-IIV-MEMORIA-PROJETO.md secção 6), nunca uma tabela partilhada
 * com um discriminador de estação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonio_central', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments');
            $table->string('responsible_user')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 14, 2)->default(0);
            $table->decimal('current_value', 14, 2)->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->default('activo');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimonio_central');
    }
};
