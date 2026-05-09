<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class DashboardTopProducts extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Top products (last 7 days)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->select([
                        // Synthetic id so Filament's record key works on grouped rows
                        DB::raw('MIN(order_items.id) as id'),
                        'order_items.sku',
                        'order_items.name',
                        DB::raw('SUM(order_items.qty) as total_qty'),
                        DB::raw('SUM(order_items.line_total) as total_revenue'),
                        DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                    ])
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.created_at', '>=', now()->subDays(7))
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    // Skip unpriced grocery requests
                    ->where('order_items.is_grocery_request', false)
                    ->groupBy('order_items.sku', 'order_items.name')
                    ->orderByDesc('total_qty')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->wrap()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Sold')
                    ->numeric()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('PKR')
                    ->weight('semibold'),
            ]);
    }
}
