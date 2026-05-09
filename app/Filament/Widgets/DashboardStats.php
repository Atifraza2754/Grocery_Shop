<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The 4 core dashboard tiles per spec:
 *  - Total orders today
 *  - Revenue today
 *  - Pending orders
 *  - Active customers (#)
 *
 * Auto-discovered by AdminPanelProvider's discoverWidgets().
 */
class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        // Orders + revenue today (excluding cancelled)
        $ordersToday = Order::query()
            ->where('created_at', '>=', $today)
            ->whereNot('status', Order::STATUS_CANCELLED)
            ->count();

        $revenueToday = (float) Order::query()
            ->where('created_at', '>=', $today)
            ->whereNot('status', Order::STATUS_CANCELLED)
            ->sum('total');

        // Pending — awaiting confirmation
        $pendingOrders = Order::where('status', Order::STATUS_PENDING)->count();

        // Active customers — placed an order in last 7 days
        $activeCustomers = Customer::where('total_orders', '>', 0)
            ->where('last_order_at', '>=', now()->subDays(7))
            ->count();

        $allOrdersUrl     = url('/admin/orders');
        $pendingOrdersUrl = url('/admin/orders?tableFilters[status][value]=pending');
        $customersUrl     = url('/admin/customers');

        return [
            Stat::make('Orders today', (string) $ordersToday)
                ->description('Placed today (excl. cancelled)')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->url($allOrdersUrl),

            Stat::make('Revenue today', 'Rs ' . number_format($revenueToday, 0))
                ->description('Total bill value, today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Pending orders', (string) $pendingOrders)
                ->description($pendingOrders > 0 ? 'Waiting to be confirmed' : 'All caught up')
                ->descriptionIcon($pendingOrders > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray')
                ->url($pendingOrdersUrl),

            Stat::make('Active customers', (string) $activeCustomers)
                ->description('Ordered in last 7 days')
                ->descriptionIcon('heroicon-m-users')
                ->icon('heroicon-o-users')
                ->color('info')
                ->url($customersUrl),
        ];
    }

    public function getColumns(): int
    {
        return 4;
    }
}
