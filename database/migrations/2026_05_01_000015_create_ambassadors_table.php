<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambassadors', function (Blueprint $table) {
            $table->id();

            // Optional link to a User account (for future ambassador login).
            // Skipped from form for now per "in advance" rule.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('phone', 20)->nullable()->index();
            $table->string('email')->nullable();

            // Coverage
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->string('building')->nullable();

            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('commission_plans')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['area_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambassadors');
    }
};
