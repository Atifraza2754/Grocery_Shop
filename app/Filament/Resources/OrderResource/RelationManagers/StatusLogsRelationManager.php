<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusLogs';

    protected static ?string $title = 'Status history';

    protected static ?string $icon = 'heroicon-o-clock';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i:s')
                    ->since(),

                Tables\Columns\TextColumn::make('from_status')
                    ->label('From')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('to_status')
                    ->label('To')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending'          => 'gray',
                        'confirmed'        => 'info',
                        'preparing'        => 'warning',
                        'out_for_delivery' => 'primary',
                        'delivered'        => 'success',
                        'cancelled'        => 'danger',
                        default            => 'gray',
                    }),

                Tables\Columns\TextColumn::make('changedBy.name')
                    ->label('Changed by')
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('note')
                    ->wrap()
                    ->placeholder('—'),
            ]);
    }
}
