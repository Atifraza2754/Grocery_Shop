<?php

namespace App\Filament\Resources\AmbassadorResource\RelationManagers;

use App\Models\Commission;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    protected static ?string $title = 'Commissions';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.order_no')
                    ->label('Order')
                    ->badge()
                    ->color('gray')
                    ->url(fn (Commission $r) => $r->order
                        ? route('filament.admin.resources.orders.edit', ['record' => $r->order_id])
                        : null),

                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Base')
                    ->money('PKR'),

                Tables\Columns\TextColumn::make('percent')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim((string) $state, '0'), '.') . '%')
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Commission $r) => Commission::STATUSES[$r->status] ?? $r->status)
                    ->color(fn (Commission $r) => $r->statusColor()),

                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('M j, Y')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Commission::STATUSES),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Commission $r) => $r->status === Commission::STATUS_PENDING)
                    ->form([
                        Forms\Components\Select::make('paid_method')
                            ->options([
                                'cash'     => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'mobile'   => 'JazzCash / EasyPaisa',
                                'other'    => 'Other',
                            ])
                            ->default('cash')
                            ->required(),
                        Forms\Components\Textarea::make('note')->rows(2),
                    ])
                    ->action(function (Commission $record, array $data) {
                        $record->markPaid($data['paid_method'] ?? null, $data['note'] ?? null);
                        Notification::make()->title('Commission marked paid')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_paid_bulk')
                    ->label('Mark selected as paid')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('paid_method')
                            ->options([
                                'cash'     => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'mobile'   => 'JazzCash / EasyPaisa',
                                'other'    => 'Other',
                            ])
                            ->default('cash')
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $count = 0;
                        foreach ($records as $r) {
                            if ($r->status === Commission::STATUS_PENDING) {
                                $r->markPaid($data['paid_method'] ?? null);
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("{$count} commissions marked paid")
                            ->success()->send();
                    }),
            ]);
    }
}
