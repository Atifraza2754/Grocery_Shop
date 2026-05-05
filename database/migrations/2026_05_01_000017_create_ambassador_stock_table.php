<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Current balance of each stock item per ambassador.
 * Denormalized for fast querying — kept in sync via stock_movements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambassador_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambassador_id')
                ->constrained('ambassadors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('stock_item_id')
                ->constrained('stock_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('qty', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['ambassador_id', 'stock_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambassador_stock');
    }
};
