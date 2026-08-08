<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Registos de inseminação artificial (Onda 9). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insemination_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('animal_id')->constrained('animals')->restrictOnDelete();
            $table->foreignUuid('dose_id')->nullable()->constrained('semen_doses')->nullOnDelete();
            $table->foreignUuid('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('insemination_date');
            $table->string('result')->default('pendente');
            $table->date('pregnancy_confirmed_at')->nullable();
            $table->date('expected_birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insemination_records');
    }
};
