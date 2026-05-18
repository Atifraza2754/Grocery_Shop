<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a free-form `margin` column to products.
 *
 * Stored as a decimal so it can hold either a Rs amount or a percent number —
 * the admin types in whatever value they want. The existing `cost_price` and
 * `margin_amount` accessor are unaffected; this is a separate manual field
 * for now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('margin', 10, 2)
                ->default(0)
                ->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('margin');
        });
    }
};
