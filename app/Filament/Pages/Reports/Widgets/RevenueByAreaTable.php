<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Area;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class RevenueByAreaTable extends BaseWidget
{
    protected static ?string $heading = 'Revenue by area (last 30 days)';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Area::query()
                    ->select([
                        'areas.id',
                        'areas.name',
                        'areas.delivery_charge',
                        DB::raw('COUNT(orders.id) as orders_count'),
                        DB::raw('COALESCE(SUM(orders.total),0) as total_revenue'),
                        DB::raw('COALESCE(AVG(orders.total),0) as avg_order_value'),
                    ])
                    ->leftJoin('orders', function ($join) {
                        $join->on('orders.area_id', '=', 'areas.id')
                            ->where('orders.created_at', '>=', now()->subDays(30))
                            ->whereNotIn('orders.status', [Order::STATUS_CANCELLED]);
                    })
                    ->groupBy('areas.id', 'areas.name', 'areas.delivery_charge')
                    ->orderByDesc('total_revenue')
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Area')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('avg_order_value')
                    ->label('AOV')
                    ->money('PKR'),

                Tables\Columns\TextColumn::make('delivery_charge')
                    ->label('Delivery rate')
                    ->money('PKR'),
            ]);
    }
}
