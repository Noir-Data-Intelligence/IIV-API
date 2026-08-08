<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `assignedToName`/`assignedDepartmentName` do DTO NÃO são colunas — são
     * resolvidos via join (whenLoaded) no Resource, tal como o handler mock
     * faz manualmente (ver dashboardAlertHistory.ts `resolve()`).
     */
    public function up(): void
    {
        Schema::create('dashboard_alert_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('metric_key');
            $table->string('label');
            $table->decimal('value', 12, 2);
            $table->decimal('threshold', 12, 2);
            $table->string('tone'); // warning|destructive
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('action_status')->default('pendente'); // pendente|em_curso|resolvido
            $table->text('action_notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'metric_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_alert_history');
    }
};
