<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Make commissions support the stock-release flow:
 * - order_id becomes nullable
 * - new stock_movement_id FK
 * - new plan_id FK
 * - new paid_amount column
 *
 * Idempotent: safe to re-run after a partial failure. Each destructive
 * step is wrapped in try/catch and each additive step checks first.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------- 1. Drop the FK on order_id (if exists) ---------- */
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropForeign(['order_id']);
        }));

        /* ---------- 2. Drop the unique index (if exists) ---------- */
        // Try common Laravel-default name first, then the column-tuple form.
        $this->silent(fn () => DB::statement(
            'ALTER TABLE `commissions` DROP INDEX `commissions_ambassador_id_order_id_unique`'
        ));
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropUnique(['ambassador_id', 'order_id']);
        }));

        /* ---------- 3. Make order_id nullable ---------- */
        Schema::table('commissions', function (Blueprint $t) {
            $t->unsignedBigInteger('order_id')->nullable()->change();
        });

        /* ---------- 4. Add new columns (skip if any already exist) ---------- */
        Schema::table('commissions', function (Blueprint $t) {
            if (! Schema::hasColumn('commissions', 'stock_movement_id')) {
                $t->foreignId('stock_movement_id')
                    ->nullable()
                    ->after('order_id')
                    ->constrained('stock_movements')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('commissions', 'plan_id')) {
                $t->foreignId('plan_id')
                    ->nullable()
                    ->after('stock_movement_id')
                    ->constrained('commission_plans')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('commissions', 'paid_amount')) {
                $t->decimal('paid_amount', 12, 2)
                    ->default(0)
                    ->after('amount');
            }
        });

        /* ---------- 5. Re-add FK on order_id (if missing) ---------- */
        if (! $this->foreignKeyExists('commissions', 'order_id')) {
            Schema::table('commissions', function (Blueprint $t) {
                $t->foreign('order_id')
                    ->references('id')->on('orders')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        /* ---------- 6. Add the new unique indexes (if missing) ---------- */
        if (! $this->indexExists('commissions', 'commissions_amb_order_unique')) {
            $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
                $t->unique(['ambassador_id', 'order_id'], 'commissions_amb_order_unique');
            }));
        }
        if (! $this->indexExists('commissions', 'commissions_amb_movement_unique')) {
            $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
                $t->unique(['ambassador_id', 'stock_movement_id'], 'commissions_amb_movement_unique');
            }));
        }
    }

    public function down(): void
    {
        // Reverse order; also defensive.
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropUnique('commissions_amb_movement_unique');
        }));
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropUnique('commissions_amb_order_unique');
        }));

        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropForeign(['plan_id']);
        }));
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropForeign(['stock_movement_id']);
        }));
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->dropForeign(['order_id']);
        }));

        Schema::table('commissions', function (Blueprint $t) {
            if (Schema::hasColumn('commissions', 'plan_id'))           $t->dropColumn('plan_id');
            if (Schema::hasColumn('commissions', 'stock_movement_id')) $t->dropColumn('stock_movement_id');
            if (Schema::hasColumn('commissions', 'paid_amount'))       $t->dropColumn('paid_amount');
        });

        Schema::table('commissions', function (Blueprint $t) {
            $t->unsignedBigInteger('order_id')->nullable(false)->change();
        });

        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->foreign('order_id')
                ->references('id')->on('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        }));
        $this->silent(fn () => Schema::table('commissions', function (Blueprint $t) {
            $t->unique(['ambassador_id', 'order_id']);
        }));
    }

    /* =================================================================== */

    /** Run a callable, swallow + log any DB error. */
    protected function silent(callable $fn): void
    {
        try { $fn(); } catch (\Throwable $e) {
            Log::info('[migration link_commissions_to_stock_movements] skipped: ' . $e->getMessage());
        }
    }

    /** Does an FK exist on (table, column)? */
    protected function foreignKeyExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT 1 AS ok
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = ?
               AND COLUMN_NAME  = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$table, $column]
        );
        return (bool) $row;
    }

    /** Does an index with this name exist on the table? */
    protected function indexExists(string $table, string $indexName): bool
    {
        $row = DB::selectOne(
            "SELECT 1 AS ok
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = ?
               AND INDEX_NAME   = ?
             LIMIT 1",
            [$table, $indexName]
        );
        return (bool) $row;
    }
};
