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
     * `actor_id` é `nullOnDelete()` — sem uma cópia desnormalizada do nome/
     * email, apagar um utilizador (mesmo legitimamente) apaga também a
     * autoria de tudo o que ele fez no trilho de auditoria. Auditoria de
     * segurança (2026-08-09): combinado com a ausência de soft-delete em
     * `users`, isto tornava o trilho de auditoria inútil para reconstituir
     * "quem fez o quê" assim que uma conta deixasse de existir.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_name')->nullable()->after('actor_id');
            $table->string('actor_email')->nullable()->after('actor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_name', 'actor_email']);
        });
    }
};
