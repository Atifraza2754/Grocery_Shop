<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Nullable so we don't lose the order line if a product is hard-deleted.
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            // Snapshot fields — always populated even if product later changes.
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('unit', 32)->default('piece');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('qty', 10, 3)->default(1);
            $table->decimal('line_total', 12, 2)->default(0);

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
