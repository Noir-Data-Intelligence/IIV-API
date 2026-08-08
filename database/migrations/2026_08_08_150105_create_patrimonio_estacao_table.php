<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Património das estações zootécnicas (Onda 7). Esquema separado de
 * `patrimonio_central` — dualidade obrigatória do projecto (ver
 * SIG-IIV-MEMORIA-PROJETO.md secção 6). `station_id` é propositadamente uma
 * coluna solta sem `->constrained()`: a entidade `estacoes` ainda não existe
 * como tabela real (só na Onda 9 — Recursos & Produção), mesmo padrão de
 * FK-circular-adiada já usado para `current_step_id`/`current_version_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonio_estacao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->uuid('station_id');
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
        Schema::dropIfExists('patrimonio_estacao');
    }
};
