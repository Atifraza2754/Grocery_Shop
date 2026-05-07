<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a flag to mark customer-typed "grocery" items that need admin pricing.
 * Existing rows default to false — no behaviour change for old orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_grocery_request')
                ->default(false)
                ->after('product_id')
                ->comment('Customer-typed item, admin must set price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_grocery_request');
        });
    }
};
