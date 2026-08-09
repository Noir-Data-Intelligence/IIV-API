<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * `MissionWorkflowService::aprovar()` mudava o estado para "aprovada"
     * sem gravar QUEM aprovou — para um instituto sujeito a auditoria
     * externa, um trilho de aprovação sem autor é tão mau como não ter
     * trilho nenhum (auditoria de segurança, 2026-08-09).
     */
    public function up(): void
    {
        Schema::table('missoes', function (Blueprint $table) {
            $table->foreignUuid('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
        });
    }
};
