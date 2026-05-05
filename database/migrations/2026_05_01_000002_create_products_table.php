<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();

            $table->string('unit', 32)->default('piece')
                ->comment('kg, gram, piece, pack, dozen, ml, l, etc.');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_price', 10, 2)->nullable()
                ->comment('Optional MRP/strikethrough price');

            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->string('image')->nullable()->comment('Main/cover image');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
