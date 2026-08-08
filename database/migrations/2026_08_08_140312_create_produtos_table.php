<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Catálogo de vacinas/soros/reagentes veterinários (Onda 4 — Produção). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('product_type');
            $table->text('description')->nullable();
            $table->string('unit');
            $table->boolean('is_archived')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
