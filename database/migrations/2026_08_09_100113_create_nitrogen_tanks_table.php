<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tanques de nitrogénio de um centro de IA (Onda 9). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nitrogen_tanks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('center_id')->constrained('ia_centers')->restrictOnDelete();
            $table->string('code')->default('SEM-COD');
            $table->decimal('capacity_l', 10, 2)->default(0);
            $table->decimal('current_level_l', 10, 2)->default(0);
            $table->decimal('min_level_l', 10, 2)->default(0);
            $table->date('last_refill_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nitrogen_tanks');
    }
};
