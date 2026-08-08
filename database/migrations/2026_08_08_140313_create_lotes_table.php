<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lotes de produção — 1 produto (N lotes). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_number')->unique();
            $table->foreignUuid('product_id')->constrained('produtos');
            $table->unsignedInteger('quantity_produced')->default(0);
            $table->unsignedInteger('quantity_distributed')->default(0);
            $table->date('production_date');
            $table->date('expiry_date');
            $table->string('status')->default('planeada');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
