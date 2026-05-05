<?php

namespace App\Filament\Resources\AmbassadorResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockBalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'stockBalances';

    protected static ?string $title = 'Current stock';

    protected static ?string $icon = 'heroicon-o-cube';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('stockItem.name')
                    ->label('Item')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->formatStateUsing(
                        fn ($state, $record) => rtrim(rtrim(number_format((float) $state, 3), '0'), '.')
                            . ' ' . ($record->stockItem->unit ?? '')
                    )
                    ->badge()
                    ->color(fn ($record) => $record->qty > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last change')
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('qty', 'desc');
    }
}
