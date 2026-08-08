<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_alert_acks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('metric_key');
            $table->timestamp('acknowledged_until');

            $table->unique(['user_id', 'metric_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_alert_acks');
    }
};
