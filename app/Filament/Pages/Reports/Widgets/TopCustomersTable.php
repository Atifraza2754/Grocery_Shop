<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopCustomersTable extends BaseWidget
{
    protected static ?string $heading = 'Top customers (all-time)';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->where('total_orders', '>', 0)
                    ->orderByDesc('total_spend')
                    ->limit(10)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (Customer $r) => $r->phone),

                Tables\Columns\TextColumn::make('area.name')
                    ->placeholder('—')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_spend')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('avg_order_value')
                    ->label('AOV')
                    ->state(fn (Customer $r) => (float) $r->avg_order_value)
                    ->money('PKR'),

                Tables\Columns\TextColumn::make('segment_label')
                    ->label('Segment')
                    ->badge()
                    ->color(fn (Customer $r) => $r->segment_color),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Last order')
                    ->since(),
            ]);
    }
}
