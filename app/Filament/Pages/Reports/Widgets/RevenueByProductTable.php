<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

/**
 * Revenue by product. By default lists the top 100 most-ordered products
 * (those with the highest order count), showing each product's name, how
 * many orders it appeared in, and the total revenue it generated. A product
 * dropdown lets you filter down to a single product.
 */
class RevenueByProductTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.pages.reports.widgets.revenue-by-product-table';

    /** Selected product (null = top 100 across all products). */
    public ?int $productId = null;

    public function updatedProductId(): void
    {
        $this->resetTable();
    }

    /** Product options for the dropdown. */
    public function getProductOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $p) => [
                $p->id => ($p->sku ? $p->sku . ' — ' : '') . $p->name,
            ])
            ->all();
    }

    /* ---------- Table ---------- */

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->select([
                        // Synthetic stable key for the grouped row.
                        DB::raw('MIN(order_items.id) as id'),
                        'order_items.product_id',
                        DB::raw('MAX(products.name) as product_name'),
                        DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                        DB::raw('COALESCE(SUM(order_items.line_total), 0) as total_revenue'),
                    ])
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    ->whereNotNull('order_items.product_id')
                    ->when($this->productId, fn ($q) => $q->where('order_items.product_id', $this->productId))
                    ->groupBy('order_items.product_id')
                    ->orderByDesc('orders_count')
                    ->limit(100)
            )
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No product sales yet')
            ->emptyStateIcon('heroicon-o-cube')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->wrap()
                    ->weight('semibold')
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('products.name', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('PKR')
                    ->weight('semibold'),
            ]);
    }
}
