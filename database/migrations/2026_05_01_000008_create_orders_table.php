<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()
                ->comment('Human-readable ID, e.g. ORD-20260502-0001');

            // Customer linkage — keep the FK but also snapshot the customer
            // info so the order is historically accurate even if customer
            // record is later edited or deleted.
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->string('customer_name');
            $table->string('customer_phone', 20)->index();

            // Delivery
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->text('delivery_address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Money
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Coupon snapshot
            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons')
                ->nullOnDelete();
            $table->string('coupon_code', 32)->nullable();

            // Status workflow
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'out_for_delivery',
                'delivered',
                'cancelled',
            ])->default('pending')->index();

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            $table->enum('payment_method', [
                'cod',
                'cash',
                'transfer',
                'other',
            ])->default('cod');

            $table->text('notes')->nullable()->comment('Internal admin notes');
            $table->text('customer_note')->nullable()->comment('Note from the customer');

            // Who placed the order on the admin side
            $table->foreignId('placed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Lifecycle timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
