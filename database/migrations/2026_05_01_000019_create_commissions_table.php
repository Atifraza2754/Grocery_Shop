<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambassador_id')
                ->constrained('ambassadors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Snapshot at time of generation
            $table->decimal('base_amount', 12, 2)->default(0)
                ->comment('Order subtotal − discount; the amount commission is calculated from');
            $table->decimal('percent', 5, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);

            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('paid_method')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['ambassador_id', 'order_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
