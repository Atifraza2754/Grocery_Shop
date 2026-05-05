<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbassadorResource\Pages;
use App\Models\Ambassador;
use App\Models\Area;
use App\Models\CommissionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AmbassadorResource extends Resource
{
    protected static ?string $model = Ambassador::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Ambassadors';
    protected static ?string $navigationLabel = 'Ambassadors';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(120),

                    Forms\Components\TextInput::make('phone')
                        ->tel()->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->email()->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Coverage & plan')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('area_id')
                        ->label('Area')
                        ->options(
                            fn () => Area::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')->orderBy('name')
                                ->pluck('name', 'id')->all()
                        )
                        ->searchable(),

                    Forms\Components\TextInput::make('building')
                        ->maxLength(120)
                        ->placeholder('e.g. Marina Tower, Block A'),

                    Forms\Components\Select::make('plan_id')
                        ->label('Commission plan')
                        ->options(
                            fn () => CommissionPlan::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (CommissionPlan $p) => [
                                    $p->id => $p->name . ' (' . rtrim(rtrim((string) $p->percent, '0'), '.') . '%)',
                                ])
                                ->all()
                        )
                        ->searchable()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->weight('semibold')
                    ->description(fn (Ambassador $r) => $r->phone),

                Tables\Columns\TextColumn::make('area.name')
                    ->placeholder('—')->badge()->color('gray'),

                Tables\Columns\TextColumn::make('building')
                    ->placeholder('—')->limit(28)->toggleable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->placeholder('—')
                    ->badge()->color('info')
                    ->formatStateUsing(function (Ambassador $r) {
                        $p = $r->plan;
                        if (! $p) return '—';
                        return $p->name . ' (' . rtrim(rtrim((string) $p->percent, '0'), '.') . '%)';
                    }),

                Tables\Columns\TextColumn::make('orders_handled_count')
                    ->label('Delivered')
                    ->state(fn (Ambassador $r) => $r->orders_handled_count)
                    ->numeric()
                    ->badge()->color('success'),

                Tables\Columns\TextColumn::make('revenue_generated')
                    ->label('Revenue generated')
                    ->state(fn (Ambassador $r) => (float) $r->revenue_generated)
                    ->money('PKR')
                    ->color('info'),

                Tables\Columns\TextColumn::make('commission_pending')
                    ->label('Pending payout')
                    ->state(fn (Ambassador $r) => (float) $r->commission_pending)
                    ->money('PKR')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('commission_paid')
                    ->label('Paid out')
                    ->state(fn (Ambassador $r) => (float) $r->commission_paid)
                    ->money('PKR')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name')
                    ->preload()->searchable(),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            AmbassadorResource\RelationManagers\StockBalancesRelationManager::class,
            AmbassadorResource\RelationManagers\CommissionsRelationManager::class,
            AmbassadorResource\RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAmbassadors::route('/'),
            'create' => Pages\CreateAmbassador::route('/create'),
            'edit'   => Pages\EditAmbassador::route('/{record}/edit'),
        ];
    }
}
