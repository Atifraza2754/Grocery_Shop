<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Manual reference ID entered by staff (our paper/manual system),
            // separate from the auto-generated order_no.
            $table->string('manual_order_id', 64)
                ->nullable()
                ->after('order_no')
                ->index()
                ->comment('Manual reference ID used in our offline system');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['manual_order_id']);
            $table->dropColumn('manual_order_id');
        });
    }
};
