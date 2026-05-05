<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\Ambassador;
use App\Models\StockItem;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Ambassadors';
    protected static ?string $navigationLabel = 'Stock Movements';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Movement')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('ambassador_id')
                        ->label('Ambassador')
                        ->required()
                        ->options(
                            fn () => Ambassador::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')->all()
                        )
                        ->searchable(),

                    Forms\Components\Select::make('stock_item_id')
                        ->label('Stock item')
                        ->required()
                        ->options(
                            fn () => StockItem::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')->all()
                        )
                        ->searchable(),

                    Forms\Components\Select::make('type')
                        ->required()
                        ->options(StockMovement::TYPES)
                        ->default(StockMovement::TYPE_ASSIGN),

                    Forms\Components\TextInput::make('qty')
                        ->numeric()->required()
                        ->minValue(0.001)
                        ->step(0.001),

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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ambassador.name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('stockItem.name')
                    ->label('Item')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => StockMovement::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        StockMovement::TYPE_ASSIGN  => 'success',
                        StockMovement::TYPE_RELEASE => 'warning',
                        StockMovement::TYPE_ADJUST  => 'info',
                        default                     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('qty')
                    ->formatStateUsing(
                        fn ($state, StockMovement $r) =>
                            ($r->type === StockMovement::TYPE_RELEASE ? '−' : '+')
                            . rtrim(rtrim(number_format((float) $state, 3), '0'), '.')
                            . ' ' . ($r->stockItem->unit ?? '')
                    )
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('By')
                    ->placeholder('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('note')
                    ->wrap()
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ambassador_id')
                    ->label('Ambassador')
                    ->relationship('ambassador', 'name')
                    ->preload()->searchable(),

                Tables\Filters\SelectFilter::make('stock_item_id')
                    ->label('Item')
                    ->relationship('stockItem', 'name')
                    ->preload()->searchable(),

                Tables\Filters\SelectFilter::make('type')
                    ->options(StockMovement::TYPES),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Deleting this will reverse its effect on the ambassador stock balance.'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ambassador', 'stockItem', 'recordedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
        ];
    }
}
