<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Customer;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CustomerTypeTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.customer-type-table';

    /** Active tab: 'repeated' or 'new'. */
    public string $type = 'repeated';

    /** Quick window in days (used when no custom range is set). */
    public int $days = 30;

    /** Custom date range (YYYY-MM-DD). When both set, overrides $days. */
    public ?string $fromDate = null;
    public ?string $toDate = null;

    /** Memoised per-request map of customer_id => orders-in-window count. */
    protected ?Collection $windowCounts = null;

    /* ---------- Tab / filter actions (called from the blade) ---------- */

    public function setType(string $type): void
    {
        $this->type = in_array($type, ['repeated', 'new'], true) ? $type : 'repeated';
        $this->resetTable();
    }

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

            // Guard against reversed range.
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to];
        }

        return [now()->subDays($this->days)->startOfDay(), now()];
    }

    /**
     * customer_id => number of REAL orders inside the window.
     * "Real" = an actually-placed order: pending (awaiting confirmation) and
     * cancelled orders are excluded, so a customer with only a pending order
     * counts as zero and never shows up as a "new" customer.
     */
    protected function getWindowCounts(): Collection
    {
        if ($this->windowCounts === null) {
            [$from, $to] = $this->windowRange();

            $this->windowCounts = Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('customer_id')
                ->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_CANCELLED])
                ->groupBy('customer_id')
                ->selectRaw('customer_id, COUNT(*) as cnt')
                ->pluck('cnt', 'customer_id');
        }

        return $this->windowCounts;
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
        $counts = $this->getWindowCounts();

        // New = exactly 1 order in window; Repeated = 2+ orders in window.
        $ids = $this->type === 'new'
            ? $counts->filter(fn ($c) => (int) $c === 1)->keys()
            : $counts->filter(fn ($c) => (int) $c >= 2)->keys();

        return $table
            ->query(
                Customer::query()
                    ->whereIn('id', $ids->all() ?: [0])
                    ->withCount(['orders as placed_orders_count' => function ($q) {
                        $q->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_CANCELLED]);
                    }])
                    ->orderByDesc('total_spend')
            )
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading(
                $this->type === 'new'
                    ? 'No new customers in this window'
                    : 'No repeated customers in this window'
            )
            ->emptyStateIcon('heroicon-o-users')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->weight('semibold')
                    ->searchable(['name', 'phone']),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->searchable(),

                Tables\Columns\TextColumn::make('placed_orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->badge()->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_spend')
                    ->label('Total spend')
                    ->money('PKR')
                    ->weight('semibold')
                    ->sortable(),

                // Average order value = total spend ÷ number of (real) orders.
                Tables\Columns\TextColumn::make('avg_order_value')
                    ->label('Average')
                    ->state(function (Customer $r) {
                        $count = (int) $r->placed_orders_count;

                        return $count > 0 ? (float) $r->total_spend / $count : 0;
                    })
                    ->money('PKR')
                    ->weight('semibold'),
            ])
            ->actions([
                // "Order details" — opens a modal listing every order this
                // customer placed, each with its products (qty + unit).
                Tables\Actions\Action::make('order_details')
                    ->label('View details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Customer $r) => $r->name . ' — order details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('2xl')
                    ->modalContent(fn (Customer $record) => view(
                        'filament.pages.reports.widgets.customer-orders-modal',
                        [
                            'customer' => $record->load(['orders' => function ($q) {
                                $q->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_CANCELLED])
                                    ->with('items')
                                    ->orderByDesc('created_at');
                            }]),
                        ]
                    )),
            ]);
    }
}
