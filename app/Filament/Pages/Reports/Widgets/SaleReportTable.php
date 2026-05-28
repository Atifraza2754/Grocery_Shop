<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sale report grouped either by day (Daily tab) or by month (Monthly tab),
 * with an optional custom date range.
 */
class SaleReportTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.sale-report-table';

    /** Active tab: 'daily' or 'monthly'. */
    public string $mode = 'daily';

    /** Custom date range (YYYY-MM-DD). When both set, overrides the default window. */
    public ?string $fromDate = null;
    public ?string $toDate = null;

    /* ---------- Tab / filter actions (called from the blade) ---------- */

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['daily', 'monthly'], true) ? $mode : 'daily';
        $this->resetTable();
    }

    public function clearCustom(): void
    {
        $this->fromDate = null;
        $this->toDate   = null;
        $this->resetTable();
    }

    public function updatedFromDate(): void
    {
        $this->resetTable();
    }

    public function updatedToDate(): void
    {
        $this->resetTable();
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

        // Default windows: last 12 months (monthly) / last 30 days (daily).
        return $this->mode === 'monthly'
            ? [now()->subMonths(11)->startOfMonth(), now()->endOfDay()]
            : [now()->subDays(29)->startOfDay(), now()->endOfDay()];
    }

    /** Human label for the active window, shown in the blade. */
    public function getWindowLabel(): string
    {
        [$from, $to] = $this->windowRange();

        if ($this->fromDate && $this->toDate) {
            return $from->toFormattedDateString() . ' – ' . $to->toFormattedDateString();
        }

        return $this->mode === 'monthly' ? 'Last 12 months' : 'Last 30 days';
    }

    /* ---------- Table ---------- */

    public function table(Table $table): Table
    {
        [$from, $to] = $this->windowRange();

        $periodExpr = $this->mode === 'monthly'
            ? "DATE_FORMAT(orders.created_at, '%Y-%m')"
            : "DATE(orders.created_at)";

        return $table
            ->query(
                Order::query()
                    ->select([
                        DB::raw('MIN(orders.id) as id'),
                        DB::raw("{$periodExpr} as period"),
                        DB::raw('COUNT(*) as orders_count'),
                        DB::raw('COALESCE(SUM(orders.total),0) as total_revenue'),
                    ])
                    ->whereBetween('orders.created_at', [$from, $to])
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    ->groupBy('period')
                    ->orderByDesc('period')
            )
            ->paginated([12, 24, 50, 100])
            ->defaultPaginationPageOption(12)
            ->emptyStateHeading('No sales in this period')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label($this->mode === 'monthly' ? 'Month' : 'Date')
                    ->weight('semibold')
                    ->formatStateUsing(function ($state) {
                        if ($this->mode === 'monthly') {
                            return Carbon::createFromFormat('Y-m', (string) $state)->format('F Y');
                        }
                        return Carbon::parse((string) $state)->format('D, M j, Y');
                    }),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('PKR')
                    ->weight('semibold'),
            ]);
    }
}
