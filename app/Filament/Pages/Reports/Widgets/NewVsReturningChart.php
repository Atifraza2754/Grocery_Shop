<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class NewVsReturningChart extends ChartWidget
{
    protected static ?string $heading = 'New vs returning customers (last 30 days)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $since = now()->subDays(30);

        $customerOrderCounts = Order::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('customer_id')
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->groupBy('customer_id')
            ->selectRaw('customer_id, COUNT(*) as cnt')
            ->pluck('cnt', 'customer_id');

        $newCount      = $customerOrderCounts->filter(fn ($c) => $c == 1)->count();
        $returningCount = $customerOrderCounts->filter(fn ($c) => $c >= 2)->count();

        return [
            'labels'   => ['New', 'Returning'],
            'datasets' => [[
                'data' => [$newCount, $returningCount],
                'backgroundColor' => ['#3b82f6', '#10b981'],
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
