<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Customer;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewStats extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $week  = now()->startOfWeek();
        $month = now()->startOfMonth();

        $delivered = fn ($q) => $q->whereNotIn('status', [Order::STATUS_CANCELLED]);

        $todayOrders   = Order::query()->where('created_at', '>=', $today)->count();
        $todayRevenue  = (float) Order::query()->where('created_at', '>=', $today)
                                ->whereNotIn('status', [Order::STATUS_CANCELLED])
                                ->sum('total');

        $weekOrders    = Order::query()->where('created_at', '>=', $week)->count();
        $weekRevenue   = (float) Order::query()->where('created_at', '>=', $week)
                                ->whereNotIn('status', [Order::STATUS_CANCELLED])
                                ->sum('total');

        $monthOrders   = Order::query()->where('created_at', '>=', $month)->count();
        $monthRevenue  = (float) Order::query()->where('created_at', '>=', $month)
                                ->whereNotIn('status', [Order::STATUS_CANCELLED])
                                ->sum('total');

        $aov = $monthOrders > 0 ? round($monthRevenue / $monthOrders, 2) : 0;

        $totalCustomers = Customer::count();
        $vipCount       = Customer::where('total_spend', '>=', 10000)->count();

        return [
            Stat::make('Orders today', $todayOrders)
                ->description('Rs ' . number_format($todayRevenue, 2))
                ->color('primary')
                ->icon('heroicon-o-shopping-bag'),

            Stat::make('Orders this week', $weekOrders)
                ->description('Rs ' . number_format($weekRevenue, 2))
                ->color('info')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Orders this month', $monthOrders)
                ->description('Rs ' . number_format($monthRevenue, 2))
                ->color('success')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Avg order value (mo)', 'Rs ' . number_format($aov, 2))
                ->description('Month-to-date')
                ->color('warning')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Total customers', $totalCustomers)
                ->description($vipCount . ' VIPs (Rs 10k+)')
                ->color('gray')
                ->icon('heroicon-o-users'),
        ];
    }

    public function getColumns(): int
    {
        return 5;
    }
}
