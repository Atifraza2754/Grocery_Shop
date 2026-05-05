<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionPlanResource\Pages;
use App\Models\CommissionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionPlanResource extends Resource
{
    protected static ?string $model = CommissionPlan::class;

    protected static ?string $navigationIcon  = 'heroicon-o-percent-badge';
    protected static ?string $navigationGroup = 'Ambassadors';
    protected static ?string $navigationLabel = 'Commission Plans';
    protected static ?int    $navigationSort  = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plan details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\TextInput::make('percent')
                        ->label('Commission %')
                        ->numeric()
                        ->required()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01),

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
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->weight('semibold'),

                Tables\Columns\TextColumn::make('percent')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim((string) $state, '0'), '.') . '%')
                    ->badge()->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ambassadors_count')
                    ->counts('ambassadors')
                    ->label('Ambassadors')
                    ->numeric(),

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
            'index'  => Pages\ListCommissionPlans::route('/'),
            'create' => Pages\CreateCommissionPlan::route('/create'),
            'edit'   => Pages\EditCommissionPlan::route('/{record}/edit'),
        ];
    }
}
