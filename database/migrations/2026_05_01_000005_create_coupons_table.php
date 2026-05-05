<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code', 32)->unique();

            // 'percent' = % off, 'flat' = fixed amount off
            $table->enum('type', ['percent', 'flat'])->default('percent');

            // Discount amount (% if type=percent, currency if type=flat)
            $table->decimal('value', 10, 2)->default(0);

            // Optional caps / conditions
            $table->decimal('min_order_amount', 10, 2)->nullable()
                ->comment('Order subtotal must be >= this for the coupon to apply');
            $table->decimal('max_discount_amount', 10, 2)->nullable()
                ->comment('Cap on discount when type=percent');

            // Usage caps
            $table->unsignedInteger('usage_limit')->nullable()
                ->comment('Total uses allowed across all customers');
            $table->unsignedInteger('usage_per_customer')->nullable()
                ->comment('How many times a single customer (by phone) can use it');
            $table->unsignedInteger('used_count')->default(0);

            // Validity window
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
