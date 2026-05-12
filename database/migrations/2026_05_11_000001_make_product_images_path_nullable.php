<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter column to be nullable. Uses a raw statement to avoid requiring doctrine/dbal.
        DB::statement("ALTER TABLE `product_images` MODIFY `path` VARCHAR(255) NULL;");
    }

    public function down(): void
    {
        // Revert to NOT NULL. Beware: this will fail if NULL values exist.
        DB::statement("ALTER TABLE `product_images` MODIFY `path` VARCHAR(255) NOT NULL;");
    }
};
