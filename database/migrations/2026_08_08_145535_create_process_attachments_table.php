<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('process_id')->constrained('processos');
            $table->string('label');
            $table->string('file_path')->nullable();
            $table->foreignUuid('document_id')->nullable()->constrained('documentos');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_attachments');
    }
};
