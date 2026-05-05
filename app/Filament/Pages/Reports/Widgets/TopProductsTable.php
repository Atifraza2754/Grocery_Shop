<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopProductsTable extends BaseWidget
{
    protected static ?string $heading = 'Top products (last 30 days)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->select([
                        // Synthetic id so Filament's getTableRecordKey() works
                        // (groupBy hides the natural id, so we grab the smallest one).
                        DB::raw('MIN(order_items.id) as id'),
                        'order_items.sku',
                        'order_items.name',
                        DB::raw('SUM(order_items.qty) as total_qty'),
                        DB::raw('SUM(order_items.line_total) as total_revenue'),
                        DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                    ])
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.created_at', '>=', now()->subDays(30))
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    ->groupBy('order_items.sku', 'order_items.name')
                    ->orderByDesc('total_qty')
                    ->limit(10)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->badge()->color('gray'),
                Tables\Columns\TextColumn::make('name')
                    ->wrap()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Qty sold')
                    ->numeric()
                    ->badge()->color('success'),
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
