<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Etapas-padrão de um tipo de processo — sem timestamps, `ProcessTypeStepDto` não os tem. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_type_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('process_type_id')->constrained('process_types');
            $table->unsignedInteger('order_index');
            $table->string('name');
            $table->string('default_role')->nullable();
            $table->unsignedInteger('sla_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_type_steps');
    }
};
