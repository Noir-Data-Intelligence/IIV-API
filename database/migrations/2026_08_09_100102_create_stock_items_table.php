<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Itens de stock (Onda 9). Auto-contido — sem FK para `insumos` (Onda 3) nem
 * `produtos`/`lotes` (Onda 4): verificado directamente no contrato real
 * (`StockItemDto`), apesar da aspiração de integração automática descrita
 * nos documentos de requisitos. `category` é um enum de string livre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category')->default('laboratorio');
            $table->string('sku')->nullable();
            $table->string('unit')->default('unidade');
            $table->decimal('quantity', 14, 2)->default(0);
            $table->decimal('min_stock', 14, 2)->default(0);
            $table->foreignUuid('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
