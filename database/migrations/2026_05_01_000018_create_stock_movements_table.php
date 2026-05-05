<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of every stock change. The ambassador_stock balance
 * should always equal the sum of movements for that pair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambassador_id')
                ->constrained('ambassadors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('stock_item_id')
                ->constrained('stock_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // 'assign' = stock given to ambassador (+qty)
            // 'release' = stock taken back / consumed / sold (-qty)
            // 'adjust' = manual correction (signed qty)
            $table->enum('type', ['assign', 'release', 'adjust'])->default('assign');

            // Always positive; 'release'/'adjust' apply the sign internally.
            $table->decimal('qty', 12, 3)->default(0);

            // Optional reference to an order or other entity.
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('note')->nullable();

            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['ambassador_id', 'created_at']);
            $table->index(['stock_item_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
