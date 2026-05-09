<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DashboardPendingOrders extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Pending orders — needs your action';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->where('status', Order::STATUS_PENDING)
                    ->with(['area'])
                    ->latest()
                    ->limit(10)
            )
            ->paginated(false)
            ->emptyStateHeading('All orders confirmed')
            ->emptyStateDescription('No pending orders waiting for action.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Order #')
                    ->badge()->color('gray')
                    ->weight('semibold')
                    ->url(fn (Order $r) => url('/admin/orders/' . $r->id . '/edit')),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $r) => $r->customer_phone)
                    ->wrap(),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->placeholder('—')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('pricing')
                    ->label('Pricing')
                    ->state(fn (Order $r) => $r->needsPricing() ? 'Needs pricing' : 'Priced')
                    ->badge()
                    ->color(fn ($state) => $state === 'Needs pricing' ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->changeStatus(Order::STATUS_CONFIRMED);
                        Notification::make()
                            ->title('Order confirmed')
                            ->body($record->order_no)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Order $r) => url('/admin/orders/' . $r->id . '/edit')),
            ]);
    }
}
