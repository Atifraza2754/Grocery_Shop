<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PeakOrderTimeChart extends ChartWidget
{
    protected static ?string $heading = 'Peak order time (last 30 days)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        // Bucket orders by hour of day in DB-driver-agnostic way.
        $rows = Order::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->selectRaw(self::hourExpr() . ' as hour, COUNT(*) as cnt')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $labels = [];
        $data   = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $data[]   = (int) ($rows->get($h)->cnt ?? 0);
        }

        return [
            'datasets' => [[
                'label'           => 'Orders',
                'data'            => $data,
                'backgroundColor' => 'rgba(46, 125, 50, 0.7)',
                'borderColor'     => '#2e7d32',
                'borderWidth'     => 1,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'x' => ['title' => ['display' => true, 'text' => 'Hour of day']],
            ],
        ];
    }

    /**
     * Pick the right SQL function for the active driver.
     */
    protected static function hourExpr(): string
    {
        $driver = DB::connection()->getDriverName();
        return match ($driver) {
            'sqlite' => "CAST(strftime('%H', created_at) AS INTEGER)",
            'pgsql'  => 'EXTRACT(HOUR FROM created_at)',
            default  => 'HOUR(created_at)',  // mysql / mariadb
        };
    }
}
