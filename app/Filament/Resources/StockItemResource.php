<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockItemResource\Pages;
use App\Models\StockItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockItemResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Ambassadors';
    protected static ?string $navigationLabel = 'Stock Items';
    protected static ?int    $navigationSort  = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Item details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->helperText('e.g. Frozen Kofta - Chicken'),

                    Forms\Components\Select::make('unit')
                        ->required()
                        ->default('piece')
                        ->options([
                            'piece' => 'Piece',
                            'pack'  => 'Pack',
                            'box'   => 'Box',
                            'g'     => 'g',
                            'kg'    => 'kg',
                            'ml'    => 'ml',
                            'l'     => 'l',
                            'dozen' => 'Dozen',
                        ]),

                    Forms\Components\TextInput::make('price')
                        ->label('Price (per unit)')
                        ->numeric()
                        ->required()
                        ->prefix('Rs')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->helperText('Used for commission calculation when stock is released.'),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->weight('semibold'),

                Tables\Columns\TextColumn::make('unit')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('PKR')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Total in field')
                    ->state(fn (StockItem $r) => (float) $r->ambassadorBalances()->sum('qty'))
                    ->formatStateUsing(
                        fn ($state, StockItem $r) => rtrim(rtrim(number_format((float) $state, 3), '0'), '.')
                            . ' ' . $r->unit
                    )
                    ->badge()->color('info'),

                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockItems::route('/'),
            'create' => Pages\CreateStockItem::route('/create'),
            'edit'   => Pages\EditStockItem::route('/{record}/edit'),
        ];
    }
}
