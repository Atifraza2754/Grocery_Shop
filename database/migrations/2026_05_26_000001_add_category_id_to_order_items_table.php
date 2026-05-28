<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Lets a custom (grocery-request) item be assigned to a category
            // so it shows under that category column in PO Print, just like a
            // catalog product. Catalog items keep resolving their category via
            // the linked product.
            $table->foreignId('category_id')
                ->nullable()
                ->after('product_id')
                ->constrained('categories')
                ->nullOnDelete();

            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
