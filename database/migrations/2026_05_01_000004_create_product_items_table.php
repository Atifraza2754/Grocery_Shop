<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Items included" inside a product (especially for pre-cut deals,
 * combo packs, etc.) — e.g. a "Veggie Pack" containing chopped onion,
 * garlic paste, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('qty', 10, 3)->default(1);
            $table->string('unit', 32)->default('g');
            $table->string('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
