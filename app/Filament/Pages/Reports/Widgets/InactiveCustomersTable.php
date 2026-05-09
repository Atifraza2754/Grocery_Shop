<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InactiveCustomersTable extends BaseWidget
{
    protected static ?string $heading = 'Inactive customers';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    /** Livewire-managed window in days. */
    public int $days = 7;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $cutoff = now()->subDays($this->days);
                return Customer::query()
                    ->where('total_orders', '>', 0)
                    ->where('last_order_at', '<', $cutoff)
                    ->orderByDesc('total_spend');
            })
            ->paginated(false)
            ->headerActions([
                $this->dayBtn(7,  '7+ days'),
                $this->dayBtn(14, '14+ days'),
                $this->dayBtn(30, '30+ days'),
                $this->dayBtn(60, '60+ days'),
            ])
            ->emptyStateHeading('No inactive customers in this window')
            ->emptyStateIcon('heroicon-o-check-circle')
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

                Tables\Columns\TextColumn::make('favorite_product')
                    ->label('Favorite')
                    ->state(fn (Customer $r) => $r->favorite_product)
                    ->placeholder('—')
                    ->limit(28),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Last order')
                    ->since(),

                Tables\Columns\TextColumn::make('segment_label')
                    ->label('Segment')
                    ->state(fn (Customer $r) => $r->segment_label)
                    ->badge()
                    ->color(fn (Customer $r) => $r->segment_color),
            ]);
    }

    protected function dayBtn(int $d, string $label): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('inactive_' . $d)
            ->label($label)
            ->color(fn () => $this->days === $d ? 'primary' : 'gray')
            ->action(function () use ($d) {
                $this->days = $d;
            });
    }
}
