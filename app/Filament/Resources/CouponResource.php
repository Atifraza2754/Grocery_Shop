<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon  = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Coupons';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Display name')
                            ->required()
                            ->maxLength(120)
                            ->helperText('e.g. "New Year Sale", "Welcome Bonus"'),

                        Forms\Components\TextInput::make('code')
                            ->label('Coupon code')
                            ->required()
                            ->maxLength(32)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state)))
                            ->helperText('Auto-uppercase. Customers will type this at checkout.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generate')
                                    ->icon('heroicon-m-sparkles')
                                    ->tooltip('Generate random code')
                                    ->action(function (Forms\Set $set) {
                                        $set('code', Coupon::generateCode(8));
                                    })
                            ),

                        Forms\Components\Select::make('type')
                            ->options([
                                Coupon::TYPE_PERCENT => 'Percentage off (%)',
                                Coupon::TYPE_FLAT    => 'Flat amount off (Rs)',
                            ])
                            ->default(Coupon::TYPE_PERCENT)
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('value')
                            ->label(fn (Forms\Get $get) => $get('type') === Coupon::TYPE_FLAT
                                ? 'Discount amount (Rs)'
                                : 'Discount percent (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->maxValue(fn (Forms\Get $get) => $get('type') === Coupon::TYPE_PERCENT ? 100 : null),

                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Conditions')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('min_order_amount')
                            ->label('Min order amount (Rs)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText('Order subtotal must be at least this value.'),

                        Forms\Components\TextInput::make('max_discount_amount')
                            ->label('Max discount cap (Rs)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->visible(fn (Forms\Get $get) => $get('type') === Coupon::TYPE_PERCENT)
                            ->helperText('Optional cap on % discount.'),

                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Total usage limit')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Total uses across all customers. Leave blank for unlimited.'),

                        Forms\Components\TextInput::make('usage_per_customer')
                            ->label('Per-customer limit')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Per phone number. Leave blank for unlimited.'),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->seconds(false)
                            ->helperText('Optional. Leave blank to start immediately.'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->seconds(false)
                            ->helperText('Optional. Leave blank for no expiry.'),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Code copied!')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'percent' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state) => $state === 'percent' ? 'Percent' : 'Flat'),

                Tables\Columns\TextColumn::make('value_label')
                    ->label('Value')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('min_order_amount')
                    ->label('Min order')
                    ->money('PKR')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(fn (Coupon $r) => $r->usage_limit
                        ? "{$r->used_count} / {$r->usage_limit}"
                        : (string) $r->used_count)
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('No expiry')
                    ->color(fn (?Coupon $r) => $r && $r->isExpired() ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Coupon $r) => match ($r->status_label) {
                        'Active'    => 'success',
                        'Scheduled' => 'info',
                        'Expired'   => 'danger',
                        'Used up'   => 'warning',
                        default     => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('On')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        Coupon::TYPE_PERCENT => 'Percentage',
                        Coupon::TYPE_FLAT    => 'Flat',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active'),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now())),

                Tables\Filters\Filter::make('available')
                    ->label('Currently available')
                    ->query(fn (Builder $query) => $query->available()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Coupon $r) => $r->is_active ? 'Turn off' : 'Turn on')
                    ->icon(fn (Coupon $r) => $r->is_active ? 'heroicon-m-pause' : 'heroicon-m-play')
                    ->color(fn (Coupon $r) => $r->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Coupon $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Coupon enabled' : 'Coupon disabled')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit'   => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
