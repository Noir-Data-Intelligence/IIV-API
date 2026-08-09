<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FK auto-referente separada da criação da tabela: o Postgres exige
        // que a PRIMARY KEY de `departments` já esteja totalmente
        // estabelecida antes de aceitar uma FK que aponta para ela própria
        // (SQLite é permissivo e mascarava isto) — ver `add_parent_foreign_to_departments_table`.
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
