<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Customer;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class InactiveCustomersTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.inactive-customers-table';

    /** Quick window in days (used when no custom range is set). */
    public int $days = 7;

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
            ->query(
                // Inactive = placed exactly ONE REAL order inside the window and
                // never ordered again within it. i.e. they came once during the
                // period and then went quiet. Pending (unconfirmed) and cancelled
                // orders don't count as a placed order.
                Customer::query()
                    ->whereHas('orders', function (Builder $q) use ($from, $to) {
                        $q->whereBetween('created_at', [$from, $to])
                            ->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_CANCELLED]);
                    }, '=', 1)
                    ->orderBy('name')
            )
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No inactive customers in this window')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->weight('semibold')
                    ->searchable(['name', 'phone']),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->placeholder('—')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->searchable(),
            ]);
    }
}
