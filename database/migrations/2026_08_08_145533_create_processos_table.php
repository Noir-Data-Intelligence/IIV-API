<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `current_step_id` sem FK real (referencia `process_steps`, criada depois
 * desta tabela — dependência circular processo<->etapa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->foreignUuid('type_id')->constrained('process_types');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('requester_id')->constrained('users');
            $table->string('status')->default('aberto');
            $table->string('priority')->default('normal');
            $table->date('due_date')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->uuid('current_step_id')->nullable();
            $table->string('linked_entity_type')->nullable();
            $table->uuid('linked_entity_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos');
    }
};
