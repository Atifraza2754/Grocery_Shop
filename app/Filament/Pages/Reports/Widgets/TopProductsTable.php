<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Area;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

/**
 * Top selling SKUs, broken down by area (last 30 days). Pick an area from the
 * dropdown to see only that area's best-selling products with qty + revenue.
 */
class TopProductsTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.top-products-table';

    /** Selected area (null = all areas). */
    public ?int $areaId = null;

    public function updatedAreaId(): void
    {
        $this->resetTable();
    }

    /** Area options for the dropdown. */
    public function getAreaOptions(): array
    {
        return Area::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

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
                        'orders.area_id',
                        DB::raw('MAX(areas.name) as area_name'),
                        DB::raw('SUM(order_items.qty) as total_qty'),
                        DB::raw('SUM(order_items.line_total) as total_revenue'),
                    ])
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->leftJoin('areas', 'areas.id', '=', 'orders.area_id')
                    ->where('orders.created_at', '>=', now()->subDays(30))
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    ->when($this->areaId, fn ($q) => $q->where('orders.area_id', $this->areaId))
                    ->groupBy('order_items.sku', 'order_items.name', 'orders.area_id')
                    ->orderByDesc('total_qty')
                    ->limit(100)
            )
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No sales in this area for the last 30 days')
            ->emptyStateIcon('heroicon-o-cube')
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
                    ->label('Qty Sold')
                    ->numeric()
                    ->badge()->color('success'),

                Tables\Columns\TextColumn::make('area_name')
                    ->label('Area')
                    ->placeholder('—')
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('PKR')
                    ->weight('semibold'),
            ]);
    }
}
