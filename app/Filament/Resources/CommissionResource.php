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
                        Notification::make()->title('Paid out')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    /* Pending count badge */
    public static function getNavigationBadge(): ?string
    {
        $count = Commission::where('status', Commission::STATUS_PENDING)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
