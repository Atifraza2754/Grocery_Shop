<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailyOrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Orders & revenue — last 30 days';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->copy()->subDays(29)->startOfDay();
        $end   = now()->endOfDay();

        $rows = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as cnt, COALESCE(SUM(total),0) as rev')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $labels   = [];
        $countSet = [];
        $revSet   = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key  = $cursor->toDateString();
            $row  = $rows->get($key);
            $labels[]   = $cursor->format('M j');
            $countSet[] = (int)   ($row->cnt ?? 0);
            $revSet[]   = (float) ($row->rev ?? 0);
            $cursor->addDay();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Orders',
                    'data'            => $countSet,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'tension'         => 0.3,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Revenue (Rs)',
                    'data'            => $revSet,
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.10)',
                    'tension'         => 0.3,
                    'yAxisID'         => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position'    => 'left',
                    'title'       => ['display' => true, 'text' => 'Orders'],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position'    => 'right',
                    'grid'        => ['drawOnChartArea' => false],
                    'title'       => ['display' => true, 'text' => 'Revenue'],
                ],
            ],
        ];
    }
}
