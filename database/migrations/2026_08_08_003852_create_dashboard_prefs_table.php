<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 1 linha por utilizador — recurso singular acedido via /dashboard-prefs/me. */
    public function up(): void
    {
        Schema::create('dashboard_prefs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('kpis')->nullable();
            $table->json('charts')->nullable();
            $table->json('thresholds')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_prefs');
    }
};
