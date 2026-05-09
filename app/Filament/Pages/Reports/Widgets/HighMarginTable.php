<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class HighMarginTable extends BaseWidget
{
    protected static ?string $heading = 'High margin products';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->where('cost_price', '>', 0)
                    ->select([
                        'id', 'sku', 'name', 'category_id', 'unit', 'price', 'cost_price', 'stock_qty',
                        DB::raw('(price - cost_price) AS margin_amount_calc'),
                    ])
                    ->whereRaw('(price - cost_price) > 0')
                    ->with('category')
                    ->orderByDesc('margin_amount_calc')
                    ->limit(15)
            )
            ->paginated(false)
            ->emptyStateHeading('No cost prices set yet')
            ->emptyStateDescription('Set the "Cost price" field on products to see margin reports.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->wrap()->weight('semibold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->badge()->color('info')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('cost_price')
                    ->money('PKR'),

                Tables\Columns\TextColumn::make('price')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('margin_amount')
                    ->label('Margin (Rs)')
                    ->state(fn (Product $r) => (float) $r->margin_amount)
                    ->money('PKR')
                    ->color('success')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('margin_percent')
                    ->label('Margin %')
                    ->state(fn (Product $r) => (float) $r->margin_percent)
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => $state >= 30 ? 'success' : ($state >= 15 ? 'warning' : 'gray')),
            ]);
    }
}
