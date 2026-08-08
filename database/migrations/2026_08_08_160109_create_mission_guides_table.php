<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Guia de marcha de uma missão (Onda 8) — 0..1 por missão (upsert). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_guides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->unique()->constrained('missoes')->cascadeOnDelete();
            $table->string('guide_number');
            $table->date('issue_date');
            $table->decimal('per_diem', 14, 2)->default(0);
            $table->string('transport')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_guides');
    }
};
