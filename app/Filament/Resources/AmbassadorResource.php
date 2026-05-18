<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbassadorResource\Pages;
use App\Models\Ambassador;
use App\Models\Area;
use App\Models\CommissionPlan;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Commission;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
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
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->actionsColumnLabel('Action')
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\EditAction::make(),

                /* ====================== ASSIGN STOCK (row action) ====================== */
                Tables\Actions\Action::make('assign_stock')
                    ->label('Assign stock')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('stock_item_id')
                            ->label('Stock item')
                            ->required()
                            ->options(
                                fn () => StockItem::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($i) => [
                                        $i->id => $i->name
                                            . ' (Rs ' . number_format((float) $i->price, 0) . ' / ' . $i->unit . ')',
                                    ])
                                    ->all()
                            )
                            ->searchable(),

                        Forms\Components\TextInput::make('qty')
                            ->numeric()->required()
                            ->minValue(0.001)
                            ->step(0.001),

                        Forms\Components\Textarea::make('note')
                            ->rows(2),
                    ])
                    ->action(function (Ambassador $record, array $data) {
                        $record->recordStockMovement(
                            stockItemId: (int) $data['stock_item_id'],
                            type: StockMovement::TYPE_ASSIGN,
                            qty: (float) $data['qty'],
                            note: $data['note'] ?? null,
                        );

                        Notification::make()->title('Stock assigned')->success()->send();
                    }),

                /* ====================== RELEASE STOCK (row action) ====================== */
                Tables\Actions\Action::make('release_stock')
                    ->label('Release stock')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalDescription('Releasing stock means it has been sold/distributed. The system creates a commission entry based on the chosen plan.')
                    ->form([
                        Forms\Components\Select::make('stock_item_id')
                            ->label('Stock item')
                            ->required()
                            ->options(fn (Ambassador $record) =>
                                $record->stockBalances()
                                    ->with('stockItem')
                                    ->where('qty', '>', 0)
                                    ->get()
                                    ->mapWithKeys(fn ($r) => [
                                        $r->stock_item_id => $r->stockItem->name
                                            . ' (have: ' . rtrim(rtrim((string) $r->qty, '0'), '.')
                                            . ' ' . $r->stockItem->unit
                                            . ' @ Rs ' . number_format((float) $r->stockItem->price, 0) . ')',
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->live()
                            ->placeholder('— Select item with stock —'),

                        Forms\Components\TextInput::make('qty')
                            ->numeric()->required()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->live(onBlur: true),

                        /* Plan select — defaulted to the ambassador's plan, can be overridden */
                        Forms\Components\Select::make('plan_id')
                            ->label('Commission plan')
                            ->required()
                            ->options(
                                fn () => CommissionPlan::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => $p->name . ' (' . rtrim(rtrim((string) $p->percent, '0'), '.') . '%)',
                                    ])
                                    ->all()
                            )
                            ->default(fn (Ambassador $record) => $record->plan_id)
                            ->helperText('Defaults to the ambassador\'s plan. Override per release if needed.')
                            ->live(),

                        /* Live preview of commission that will be saved */
                        Forms\Components\Placeholder::make('commission_preview')
                            ->label('Commission preview')
                            ->content(function (Forms\Get $get) {
                                $itemId = $get('stock_item_id');
                                $qty    = (float) ($get('qty') ?? 0);
                                $planId = $get('plan_id');
                                if (! $itemId || ! $qty || ! $planId) {
                                    return new HtmlString('<span class="text-gray-400">Pick item, qty, and plan to see preview.</span>');
                                }
                                $item    = StockItem::find($itemId);
                                $plan    = CommissionPlan::find($planId);
                                if (! $item || ! $plan) {
                                    return new HtmlString('—');
                                }
                                $base    = round($qty * (float) $item->price, 2);
                                $percent = (float) $plan->percent;
                                $amt     = round($base * $percent / 100, 2);
                                return new HtmlString(
                                    '<div class="space-y-1 text-sm">'
                                    . '<div>Base: Rs ' . number_format($base, 2)
                                    . '  (' . rtrim(rtrim((string) $qty, '0'), '.') . ' ' . e($item->unit)
                                    . ' × Rs ' . number_format((float) $item->price, 2) . ')</div>'
                                    . '<div>Plan: ' . e($plan->name) . ' (' . rtrim(rtrim((string) $percent, '0'), '.') . '%)</div>'
                                    . '<div class="font-semibold text-emerald-700">'
                                    . 'Commission: Rs ' . number_format($amt, 2) . '</div>'
                                    . '</div>'
                                );
                            }),

                        Forms\Components\Textarea::make('note')
                            ->rows(2),
                    ])
                    ->action(function (Ambassador $record, array $data) {
                        $movement = $record->recordStockMovement(
                            stockItemId: (int) $data['stock_item_id'],
                            type: StockMovement::TYPE_RELEASE,
                            qty: (float) $data['qty'],
                            note: $data['note'] ?? null,
                        );

                        // Generate the commission tied to this release
                        $item    = StockItem::find($data['stock_item_id']);
                        $plan    = CommissionPlan::find($data['plan_id']);
                        if ($item && $plan) {
                            $base    = round((float) $data['qty'] * (float) $item->price, 2);
                            $percent = (float) $plan->percent;
                            $amount  = round($base * $percent / 100, 2);

                            Commission::create([
                                'ambassador_id'     => $record->id,
                                'order_id'          => null,
                                'stock_movement_id' => $movement->id,
                                'plan_id'           => $plan->id,
                                'base_amount'       => $base,
                                'percent'           => $percent,
                                'amount'            => $amount,
                                'paid_amount'       => 0,
                                'status'            => Commission::STATUS_PENDING,
                                'note'              => $data['note'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Stock released + commission Rs ' . number_format($amount, 2) . ' added')
                                ->success()->send();
                        } else {
                            Notification::make()->title('Stock released')->success()->send();
                        }
                    }),
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
