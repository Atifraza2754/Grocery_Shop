<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Roll-up of products in all currently-pending orders so admin can
 * see what to prepare/buy. Has a "Copy list" action that produces a
 * clean text block to paste anywhere.
 */
class PendingProductsTable extends BaseWidget
{
    protected static ?string $heading = 'Products in pending orders';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->select([
                        DB::raw('MIN(order_items.id) as id'),
                        'order_items.sku',
                        'order_items.name',
                        'order_items.unit',
                        DB::raw('SUM(order_items.qty) as total_qty'),
                        DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                    ])
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereIn('orders.status', [
                        Order::STATUS_PENDING,
                        Order::STATUS_CONFIRMED,
                        Order::STATUS_PREPARING,
                    ])
                    ->groupBy('order_items.sku', 'order_items.name', 'order_items.unit')
                    ->orderByDesc('total_qty')
            )
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Nothing pending')
            ->emptyStateDescription('All orders are either delivered or cancelled.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->headerActions([
                Tables\Actions\Action::make('copy_list')
                    ->label('Copy list')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('success')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('lg')
                    ->modalHeading('Pending product list')
                    ->modalContent(fn () => view('filament.reports.pending-products-modal', [
                        'rows' => self::pendingRows(),
                    ])),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->badge()->color('gray')->placeholder('—'),

                Tables\Columns\TextColumn::make('name')
                    ->wrap()->weight('semibold'),

                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Need')
                    ->formatStateUsing(fn ($state, $record) =>
                        rtrim(rtrim((string) $state, '0'), '.') . ' ' . ($record->unit ?? '')
                    )
                    ->badge()->color('warning'),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric(),
            ]);
    }

    /**
     * Same query the table uses, but as raw rows for the copy-text view.
     */
    public static function pendingRows(): array
    {
        return OrderItem::query()
            ->select([
                'order_items.sku',
                'order_items.name',
                'order_items.unit',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
            ])
            ->groupBy('order_items.sku', 'order_items.name', 'order_items.unit')
            ->orderByDesc('total_qty')
            ->get()
            ->all();
    }
}
