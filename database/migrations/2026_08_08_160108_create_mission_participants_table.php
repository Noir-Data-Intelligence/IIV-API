<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Participantes de uma missão (Onda 8). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('missoes')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('role');
            $table->decimal('per_diem', 14, 2)->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_participants');
    }
};
