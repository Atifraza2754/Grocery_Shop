<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Products that have NOT been ordered inside the selected window.
 * Quick windows of 7 / 14 / 30 days, plus a custom from–to date range.
 */
class SlowMoversTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.slow-movers-table';

    /** Quick window in days (used when no custom range is set). */
    public int $days = 14;

    /** Custom date range (YYYY-MM-DD). When both set, overrides $days. */
    public ?string $fromDate = null;
    public ?string $toDate = null;

    /* ---------- Filter actions (called from the blade) ---------- */

    public function setDays(int $days): void
    {
        $this->days     = $days;
        $this->fromDate = null;
        $this->toDate   = null;
        $this->resetTable();
    }

    public function clearCustom(): void
    {
        $this->fromDate = null;
        $this->toDate   = null;
        $this->resetTable();
    }

    /** True when a quick-day window of $d is the active filter. */
    public function isDays(int $d): bool
    {
        return ! $this->fromDate && ! $this->toDate && $this->days === $d;
    }

    /* ---------- Window helpers ---------- */

    /** @return array{0: Carbon, 1: Carbon} */
    protected function windowRange(): array
    {
        if ($this->fromDate && $this->toDate) {
            $from = Carbon::parse($this->fromDate)->startOfDay();
            $to   = Carbon::parse($this->toDate)->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to];
        }

        return [now()->subDays($this->days)->startOfDay(), now()];
    }

    /** Human label for the active window, shown in the blade. */
    public function getWindowLabel(): string
    {
        [$from, $to] = $this->windowRange();

        if ($this->fromDate && $this->toDate) {
            return $from->toFormattedDateString() . ' – ' . $to->toFormattedDateString();
        }

        return "Last {$this->days} days";
    }

    /* ---------- Table ---------- */

    public function table(Table $table): Table
    {
        [$from, $to] = $this->windowRange();

        return $table
            ->query(function () use ($from, $to) {
                // Product IDs that WERE ordered inside the window (non-cancelled).
                $orderedIds = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereBetween('orders.created_at', [$from, $to])
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    ->whereNotNull('order_items.product_id')
                    ->distinct()
                    ->pluck('order_items.product_id');

                return Product::query()
                    ->where('is_active', true)
                    ->when(
                        $orderedIds->isNotEmpty(),
                        fn ($q) => $q->whereNotIn('id', $orderedIds)
                    )
                    ->with('category')
                    ->orderBy('updated_at');
            })
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Every product was ordered in this window')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('sku')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('name')->wrap()->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->badge()->color('info')->placeholder('—'),
                Tables\Columns\TextColumn::make('price')->money('PKR'),
                Tables\Columns\TextColumn::make('stock_qty')->label('Stock'),
                Tables\Columns\TextColumn::make('updated_at')->label('Last updated')->since(),
            ]);
    }
}
