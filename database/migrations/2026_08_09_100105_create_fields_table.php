<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos agrícolas (Onda 9). `station_id` usa `restrictOnDelete()` — uma
 * estação com campos activos não pode ser apagada (o mock não implementa
 * integridade referencial nenhuma para esta FK; decisão explícita do
 * backend real, ver SIG-IIV-MEMORIA-PROJETO.md). `crop_id` usa
 * `cascadeOnDelete()` — apagar uma cultura arrasta os seus campos, fiel ao
 * mock (`mocks/handlers/agricultura.ts`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('station_id')->constrained('estacoes')->restrictOnDelete();
            $table->foreignUuid('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->string('field_code')->nullable();
            $table->decimal('area_ha', 10, 2)->default(0);
            $table->date('planting_date')->nullable();
            $table->date('expected_harvest')->nullable();
            $table->string('status')->default('planeado');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
