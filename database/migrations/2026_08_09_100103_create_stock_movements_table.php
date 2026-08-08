<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Movimentos de stock (Onda 9). Registar um movimento altera
 * `stock_items.quantity` (regra de negócio no `StockMovementService`, nunca
 * no cliente) — ver `mocks/handlers/stock.ts` `applyMovementDelta`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('stock_items')->cascadeOnDelete();
            $table->string('type')->default('entrada');
            $table->decimal('quantity', 14, 2);
            $table->foreignUuid('from_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignUuid('to_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('performed_by')->nullable();
            $table->date('movement_date');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
