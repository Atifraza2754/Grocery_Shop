<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

/**
 * Products that haven't been ordered in the selected window.
 * Default 14 days; user can switch via the day-filter buttons.
 */
class SlowMoversTable extends BaseWidget
{
    protected static ?string $heading = 'Slow movers — products with no orders recently';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    /** Public Livewire prop — read by the query, mutated by the buttons. */
    public int $days = 14;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $since = now()->subDays($this->days);

                $orderedIds = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.created_at', '>=', $since)
                    ->whereNotIn('orders.status', [Order::STATUS_CANCELLED])
                    ->whereNotNull('order_items.product_id')
                    ->distinct()
                    ->pluck('order_items.product_id');

                return Product::query()
                    ->where('is_active', true)
                    ->when($orderedIds->isNotEmpty(),
                        fn ($q) => $q->whereNotIn('id', $orderedIds))
                    ->with('category')
                    ->orderBy('updated_at');
            })
            ->paginated(false)
            ->headerActions([
                $this->dayBtn(7),
                $this->dayBtn(14),
                $this->dayBtn(30),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('sku')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('name')->wrap()->weight('semibold'),
                Tables\Columns\TextColumn::make('category.name')->badge()->color('info')->placeholder('—'),
                Tables\Columns\TextColumn::make('price')->money('PKR'),
                Tables\Columns\TextColumn::make('stock_qty')->label('Stock'),
                Tables\Columns\TextColumn::make('updated_at')->label('Last updated')->since(),
            ]);
    }

    protected function dayBtn(int $d): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('window_' . $d)
            ->label('Last ' . $d . ' days')
            ->color(fn () => $this->days === $d ? 'primary' : 'gray')
            ->action(function () use ($d) {
                $this->days = $d;
            });
    }
}
