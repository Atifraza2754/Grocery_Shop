<?php

namespace App\Filament\Resources\AmbassadorResource\RelationManagers;

use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Assigned orders';

    protected static ?string $icon = 'heroicon-o-clipboard-document-list';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_no')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Order')
                    ->badge()->color('gray')
                    ->url(fn (Order $r) => route('filament.admin.resources.orders.edit', ['record' => $r->id])),

                Tables\Columns\TextColumn::make('customer_name')
                    ->description(fn (Order $r) => $r->customer_phone),

                Tables\Columns\TextColumn::make('total')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Order $r) => $r->statusLabel())
                    ->color(fn (Order $r) => $r->statusColor()),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Order::STATUSES),
            ]);
    }
}
