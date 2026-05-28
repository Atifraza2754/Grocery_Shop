<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Area;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueByAreaTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.revenue-by-area-table';

    /** Selected area (null = all areas). */
    public ?int $areaId = null;

    /** Quick window in days (used when no custom range is set). */
    public int $days = 30;

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

    public function updatedAreaId(): void
    {
        $this->resetTable();
    }

    /** True when a quick-day window of $d is the active filter. */
    public function isDays(int $d): bool
    {
        return ! $this->fromDate && ! $this->toDate && $this->days === $d;
    }

    /** Area options for the dropdown. */
    public function getAreaOptions(): array
    {
        return Area::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
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
            ->query(
                Area::query()
                    ->select([
                        'areas.id',
                        'areas.name',
                        DB::raw('COUNT(orders.id) as orders_count'),
                        DB::raw('COALESCE(SUM(orders.total),0) as total_revenue'),
                    ])
                    ->leftJoin('orders', function ($join) use ($from, $to) {
                        $join->on('orders.area_id', '=', 'areas.id')
                            ->whereBetween('orders.created_at', [$from, $to])
                            ->whereNotIn('orders.status', [Order::STATUS_CANCELLED]);
                    })
                    ->when($this->areaId, fn ($q) => $q->where('areas.id', $this->areaId))
                    ->groupBy('areas.id', 'areas.name')
                    ->orderByDesc('total_revenue')
            )
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No areas to show')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Area')
                    ->weight('semibold'),

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
