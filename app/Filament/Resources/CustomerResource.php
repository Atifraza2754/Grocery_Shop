<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'People';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(120),

                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Select::make('area_id')
                            ->label('Area')
                            ->options(
                                fn () => \App\Models\Area::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')->orderBy('name')
                                    ->pluck('name', 'id')->all()
                            )
                            ->searchable(),

                        Forms\Components\Textarea::make('address')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('lat')
                            ->numeric()
                            ->step(0.0000001)
                            ->placeholder('e.g. 24.8607'),

                        Forms\Components\TextInput::make('lng')
                            ->numeric()
                            ->step(0.0000001)
                            ->placeholder('e.g. 67.0011'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Stats (read only)')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Placeholder::make('total_orders')
                            ->label('Total orders')
                            ->content(fn (?Customer $record) => $record?->total_orders ?? 0),

                        Forms\Components\Placeholder::make('total_spend')
                            ->label('Total spend')
                            ->content(fn (?Customer $record) => 'Rs ' . number_format((float) ($record?->total_spend ?? 0), 2)),

                        Forms\Components\Placeholder::make('aov')
                            ->label('Avg order value')
                            ->content(fn (?Customer $record) => 'Rs ' . number_format((float) ($record?->avg_order_value ?? 0), 2)),

                        Forms\Components\Placeholder::make('segment_label')
                            ->label('Segment')
                            ->content(fn (?Customer $record) => $record?->segment_label ?? '—'),

                        Forms\Components\Placeholder::make('last_order_at')
                            ->label('Last order')
                            ->content(fn (?Customer $record) => $record?->last_order_at?->diffForHumans() ?? 'Never'),

                        Forms\Components\Placeholder::make('favorite_product')
                            ->label('Favorite product')
                            ->content(fn (?Customer $record) => $record?->favorite_product ?? '—'),
                    ])
                    ->visibleOn(['edit']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Customer $r) => $r->phone),

                Tables\Columns\TextColumn::make('phone')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('segment_label')
                    ->label('Segment')
                    ->badge()
                    ->color(fn (Customer $r) => $r->segment_color)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('area.name')
                    ->placeholder('—')
                    ->badge()->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_spend')
                    ->money('PKR')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('avg_order_value')
                    ->label('AOV')
                    ->state(fn (Customer $r) => (float) $r->avg_order_value)
                    ->money('PKR')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('favorite_product')
                    ->label('Favorite')
                    ->state(fn (Customer $r) => $r->favorite_product)
                    ->placeholder('—')
                    ->limit(28)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Last order')
                    ->since()
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name')
                    ->preload(),

                /* Segment filter — purely SQL based on last_order_at */
                Tables\Filters\SelectFilter::make('segment')
                    ->label('Segment')
                    ->options([
                        'active'   => '🟢 Active (last 7d)',
                        'warm'     => '🟡 Warm (7–30d)',
                        'inactive' => '🔴 Inactive (30–60d)',
                        'lost'     => '⚫ Lost (60d+)',
                        'new'      => '🆕 New (no orders)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) return $query;
                        $now = now();

                        return match ($data['value']) {
                            'active' => $query->where('total_orders', '>', 0)
                                ->where('last_order_at', '>=', $now->copy()->subDays(7)),
                            'warm' => $query->where('total_orders', '>', 0)
                                ->whereBetween('last_order_at', [$now->copy()->subDays(30), $now->copy()->subDays(7)]),
                            'inactive' => $query->where('total_orders', '>', 0)
                                ->whereBetween('last_order_at', [$now->copy()->subDays(60), $now->copy()->subDays(30)]),
                            'lost' => $query->where(function ($q) use ($now) {
                                $q->where(function ($q2) use ($now) {
                                    $q2->where('total_orders', '>', 0)
                                        ->where('last_order_at', '<', $now->copy()->subDays(60));
                                })->orWhere(function ($q2) {
                                    $q2->where('total_orders', '>', 0)
                                        ->whereNull('last_order_at');
                                });
                            }),
                            'new' => $query->where('total_orders', 0),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('vip')
                    ->label('VIP (Rs 10,000+)')
                    ->query(fn (Builder $q) => $q->where('total_spend', '>=', 10000)),

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
            ->with('area')
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            CustomerResource\RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
