<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Orders broken down into 24 one-hour slots (12 AM–1 AM, 1 AM–2 AM, …).
 * Defaults to the current date; pick a from/to date range to count how many
 * orders landed in each hour slot across that range.
 */
class OrdersByTimeRangeTable extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.orders-by-time-range-table';

    /** Date range (YYYY-MM-DD). Both default to the current date. */
    public ?string $fromDate = null;
    public ?string $toDate = null;

    /** Simple pager over the 24 hour-slots. */
    public int $page = 1;
    public int $perPage = 12;

    public function mount(): void
    {
        $today = now()->toDateString();
        $this->fromDate = $today;
        $this->toDate   = $today;
    }

    /* ---------- Pagination ---------- */

    public function getPageCount(): int
    {
        return (int) max(1, ceil(24 / $this->perPage));
    }

    /** Slots for the current page only. */
    public function getPagedBuckets(): array
    {
        return array_slice(
            $this->getHourlyBuckets(),
            ($this->page - 1) * $this->perPage,
            $this->perPage,
        );
    }

    public function nextPage(): void
    {
        if ($this->page < $this->getPageCount()) {
            $this->page++;
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    /* ---------- Window helpers ---------- */

    /** @return array{0: Carbon, 1: Carbon} */
    protected function windowRange(): array
    {
        $from = Carbon::parse($this->fromDate ?: now()->toDateString())->startOfDay();
        $to   = Carbon::parse($this->toDate ?: now()->toDateString())->endOfDay();

        // Guard against a reversed range.
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /** Human label for the active range, shown in the blade. */
    public function getWindowLabel(): string
    {
        [$from, $to] = $this->windowRange();

        return $from->isSameDay($to)
            ? $from->format('D, M j, Y')
            : $from->format('M j, Y') . ' – ' . $to->format('M j, Y');
    }

    /* ---------- Hourly buckets ---------- */

    /**
     * Order counts grouped into 24 one-hour slots for the selected date range.
     * Every slot (0–23) is always present, even when its count is zero.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function getHourlyBuckets(): array
    {
        [$from, $to] = $this->windowRange();

        $counts = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->selectRaw('HOUR(created_at) as h, COUNT(*) as c')
            ->groupBy('h')
            ->pluck('c', 'h');

        $buckets = [];

        for ($h = 0; $h < 24; $h++) {
            $start = Carbon::createFromTime($h, 0);
            $end   = Carbon::createFromTime(($h + 1) % 24, 0);

            $buckets[] = [
                'label' => $start->format('g A') . ' to ' . $end->format('g A'),
                'count' => (int) ($counts[$h] ?? 0),
            ];
        }

        return $buckets;
    }

    /** Total orders across all slots in the active range. */
    public function getTotalOrders(): int
    {
        return array_sum(array_column($this->getHourlyBuckets(), 'count'));
    }
}
