<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a free-form `location_url` column to orders so admin can paste
 * a Google Maps link directly. The existing lat/lng columns stay — they're
 * still used by the customer-side map picker.
 *
 * The Order::mapsUrl() accessor will prefer location_url if present,
 * otherwise fall back to a built link from lat/lng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('location_url', 500)
                ->nullable()
                ->after('lng');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('location_url');
        });
    }
};
