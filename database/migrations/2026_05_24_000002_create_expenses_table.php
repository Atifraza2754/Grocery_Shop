<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->string('paid_to')->nullable();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->string('bill_no')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_date');
            $table->index('expense_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
