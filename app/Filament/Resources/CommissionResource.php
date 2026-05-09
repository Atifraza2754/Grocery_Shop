<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Models\Commission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Ambassadors';
    protected static ?string $navigationLabel = 'Commissions';
    protected static ?int    $navigationSort  = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Commission')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('status')
                        ->options(Commission::STATUSES)
                        ->required(),

                    Forms\Components\TextInput::make('base_amount')
                        ->numeric()->prefix('Rs')->step(0.01),

                    Forms\Components\TextInput::make('percent')
                        ->numeric()->suffix('%')->step(0.01),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()->prefix('Rs')->step(0.01)
                        ->required(),

                    Forms\Components\TextInput::make('paid_amount')
                        ->label('Paid so far')
                        ->numeric()->prefix('Rs')->step(0.01)
                        ->default(0)
                        ->helperText('Use the "Pay" action on the list to record payments — this is just for manual fixes.'),

                    Forms\Components\Select::make('paid_method')
                        ->options([
                            'cash'     => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'mobile'   => 'JazzCash / EasyPaisa',
                            'other'    => 'Other',
                        ]),

                    Forms\Components\DateTimePicker::make('paid_at')
                        ->seconds(false),

                    Forms\Components\Textarea::make('note')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ambassador.name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Commission $r) => $r->ambassador?->phone),

                Tables\Columns\TextColumn::make('order.order_no')
                    ->label('Order')
                    ->badge()->color('gray')
                    ->searchable()
                    ->url(fn (Commission $r) => $r->order
                        ? route('filament.admin.resources.orders.edit', ['record' => $r->order_id])
                        : null),

                Tables\Columns\TextColumn::make('base_amount')
                    ->money('PKR')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('percent')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim((string) $state, '0'), '.') . '%')
                    ->badge()->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('PKR')
                    ->weight('semibold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('PKR')
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining')
                    ->label('Remaining')
                    ->state(fn (Commission $r) => (float) $r->remaining)
                    ->money('PKR')
                    ->weight('semibold')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->state(fn (Commission $r) => $r->stock_movement_id
                        ? 'Stock release'
                        : ($r->order_id ? 'Order' : '—'))
                    ->badge()
                    ->color(fn ($state) => $state === 'Stock release' ? 'info' : ($state === 'Order' ? 'success' : 'gray'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Commission $r) => Commission::STATUSES[$r->status] ?? $r->status)
                    ->color(fn (Commission $r) => $r->statusColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('Paid by')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Commission::STATUSES),

                Tables\Filters\SelectFilter::make('ambassador_id')
                    ->label('Ambassador')
                    ->relationship('ambassador', 'name')
                    ->preload()->searchable(),

                Tables\Filters\Filter::make('this_month')
                    ->label('This month')
                    ->query(fn (Builder $q) => $q->whereBetween('created_at', [
                        now()->startOfMonth(), now()->endOfMonth(),
                    ])),
            ])
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label(fn (Commission $r) =>
                        'Pay (Rs ' . number_format((float) $r->remaining, 0) . ')')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (Commission $r) => (float) $r->remaining > 0
                        && $r->status !== Commission::STATUS_CANCELLED)
                    ->modalHeading(fn (Commission $r) =>
                        'Pay ' . ($r->ambassador?->name ?? 'commission')
                        . ' — remaining Rs ' . number_format((float) $r->remaining, 2))
                    ->modalDescription('Partial payment is allowed. Enter the amount you are paying now.')
                    ->form([
                        Forms\Components\TextInput::make('pay_amount')
                            ->label('Pay amount (Rs)')
                            ->numeric()
                            ->required()
                            ->prefix('Rs')
                            ->minValue(0.01)
                            ->step(0.01)
                            ->default(fn (Commission $record) => (float) $record->remaining)
                            ->helperText('Defaults to full remaining; lower it for a partial payment.'),

                        Forms\Components\Select::make('paid_method')
                            ->label('Method')
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
                        $applied = $record->payAmount(
                            (float) $data['pay_amount'],
                            $data['paid_method'] ?? null,
                            $data['note'] ?? null,
                        );
                        $remaining = (float) $record->fresh()->remaining;
                        Notification::make()
                            ->title('Paid Rs ' . number_format($applied, 2))
                            ->body($remaining > 0
                                ? 'Remaining on this commission: Rs ' . number_format($remaining, 2)
                                : 'Commission fully paid')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_paid_bulk')
                    ->label('Mark selected fully paid')
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
                        $totalPaid = 0.0;
                        foreach ($records as $r) {
                            $remaining = (float) $r->remaining;
                            if ($remaining > 0 && $r->status !== Commission::STATUS_CANCELLED) {
                                $r->payAmount($remaining, $data['paid_method'] ?? null);
                                $count++;
                                $totalPaid += $remaining;
                            }
                        }
                        Notification::make()
                            ->title("Paid Rs " . number_format($totalPaid, 2))
                            ->body("{$count} commissions settled in full")
                            ->success()->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ambassador', 'order', 'paidBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'edit'  => Pages\EditCommission::route('/{record}/edit'),
        ];
    }

    /* Owing count badge — anything still has a balance > 0 */
    public static function getNavigationBadge(): ?string
    {
        $count = Commission::owing()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
