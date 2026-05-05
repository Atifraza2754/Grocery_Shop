<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerRetentionStats extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = now();

        $active = Customer::where('total_orders', '>', 0)
            ->where('last_order_at', '>=', $now->copy()->subDays(7))
            ->count();

        $warm = Customer::where('total_orders', '>', 0)
            ->whereBetween('last_order_at', [$now->copy()->subDays(30), $now->copy()->subDays(7)])
            ->count();

        $inactive = Customer::where('total_orders', '>', 0)
            ->whereBetween('last_order_at', [$now->copy()->subDays(60), $now->copy()->subDays(30)])
            ->count();

        $lost = Customer::where('total_orders', '>', 0)
            ->where(function ($q) use ($now) {
                $q->where('last_order_at', '<', $now->copy()->subDays(60))
                  ->orWhereNull('last_order_at');
            })
            ->count();

        // Repeat vs new (over total customers with orders)
        $repeat = Customer::where('total_orders', '>=', 2)->count();
        $newOne = Customer::where('total_orders', 1)->count();
        $totalWithOrders = max(1, $repeat + $newOne);
        $repeatRate = round(100 * $repeat / $totalWithOrders, 1);

        return [
            Stat::make('🟢 Active', $active)
                ->description('Ordered in last 7 days')
                ->color('success'),

            Stat::make('🟡 Warm', $warm)
                ->description('7–30 days ago')
                ->color('warning'),

            Stat::make('🔴 Inactive', $inactive)
                ->description('30–60 days ago')
                ->color('danger'),

            Stat::make('⚫ Lost', $lost)
                ->description('60+ days ago')
                ->color('gray'),

            Stat::make('Repeat rate', $repeatRate . '%')
                ->description("$repeat repeat / $newOne first-time")
                ->color($repeatRate >= 50 ? 'success' : 'warning')
                ->icon('heroicon-o-arrow-path'),
        ];
    }

    public function getColumns(): int
    {
        return 5;
    }
}
