<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Ambassador;
use App\Models\Area;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?int    $navigationSort  = 2;

    protected static ?string $recordTitleAttribute = 'order_no';

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* ===== LEFT COLUMN (2/3 width) ===== */
            Forms\Components\Group::make()->columnSpan(2)->schema([

                /* --- Customer --- */
                Forms\Components\Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Search customer (by phone or name)')
                            ->searchable()
                            ->options(
                                fn () => Customer::query()
                                    ->orderByDesc('last_order_at')
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn (Customer $c) => [
                                        $c->id => "{$c->phone}  —  {$c->name}",
                                    ])
                                    ->all()
                            )
                            ->getSearchResultsUsing(
                                fn (string $search): array => Customer::query()
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                            ->orWhere('phone', 'like', "%{$search}%");
                                    })
                                    ->orderByDesc('last_order_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Customer $c) => [
                                        $c->id => "{$c->phone}  —  {$c->name}",
                                    ])
                                    ->all()
                            )
                            ->getOptionLabelUsing(function ($value): ?string {
                                $c = Customer::find($value);
                                return $c ? "{$c->phone}  —  {$c->name}" : null;
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(120),
                                Forms\Components\TextInput::make('phone')->required()->tel()
                                    ->unique('customers', 'phone')->maxLength(20),
                                Forms\Components\TextInput::make('email')->email(),
                                Forms\Components\Select::make('area_id')
                                    ->label('Area')
                                    ->options(
                                        fn () => Area::query()
                                            ->where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all()
                                    )
                                    ->searchable(),
                                Forms\Components\Textarea::make('address')->rows(2),
                            ])
                            ->createOptionUsing(fn (array $data): int => Customer::create($data)->getKey())
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) return;
                                $c = Customer::find($state);
                                if (! $c) return;
                                $set('customer_name',    $c->name);
                                $set('customer_phone',   $c->phone);
                                $set('delivery_address', $c->address);
                                if ($c->area_id) $set('area_id', $c->area_id);
                                if ($c->lat)     $set('lat', $c->lat);
                                if ($c->lng)     $set('lng', $c->lng);
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Name (snapshot)')
                            ->required()->maxLength(120),

                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Phone (snapshot)')
                            ->required()->tel()->maxLength(20),

                        Forms\Components\Textarea::make('delivery_address')
                            ->rows(2)->columnSpanFull(),

                        Forms\Components\TextInput::make('lat')
                            ->numeric()->step(0.0000001)->placeholder('Optional'),
                        Forms\Components\TextInput::make('lng')
                            ->numeric()->step(0.0000001)->placeholder('Optional'),
                    ]),

                /* --- Items --- */
                Forms\Components\Section::make('Items')
                    ->description('Grocery (custom) items have an editable name. Set price + qty and save — totals auto-recalc.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->columns(12)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->minItems(1)
                            ->addActionLabel('+ Add product')
                            ->live()
                            ->schema([
                                /* ===== ROW 1: Product + Name (5 + 7 = 12) ===== */
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(
                                        fn () => Product::query()
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn (Product $p) => [
                                                $p->id => "{$p->sku} — {$p->name}",
                                            ])
                                            ->all()
                                    )
                                    ->searchable()
                                    ->placeholder(fn (Get $get) => $get('is_grocery_request') ? '— Custom item —' : 'Pick a product')
                                    ->disabled(fn (Get $get) => (bool) $get('is_grocery_request'))
                                    ->dehydrated()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (! $state) return;
                                        $p = Product::find($state);
                                        if (! $p) return;
                                        $set('sku',   $p->sku);
                                        $set('name',  $p->name);
                                        $set('unit',  $p->unit);
                                        $set('price', (float) $p->price);
                                    })
                                    ->columnSpan(['default' => 12, 'md' => 5]),

                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(180)
                                    ->columnSpan(['default' => 12, 'md' => 7]),

                                /* ===== ROW 2: Qty + Unit + Price + Total (3 + 2 + 3 + 4 = 12) ===== */
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()->required()
                                    ->default(1)->minValue(0)->step(0.001)
                                    ->live(onBlur: true)
                                    ->columnSpan(['default' => 4, 'md' => 3]),

                                Forms\Components\TextInput::make('unit')
                                    ->maxLength(32)
                                    ->columnSpan(['default' => 4, 'md' => 2]),

                                Forms\Components\TextInput::make('price')
                                    ->numeric()->required()
                                    ->prefix('Rs')
                                    ->live(onBlur: true)
                                    ->placeholder(fn (Get $get) => $get('is_grocery_request') ? 'Set price' : null)
                                    ->columnSpan(['default' => 4, 'md' => 3]),

                                Forms\Components\Placeholder::make('line_total')
                                    ->label('Line total')
                                    ->content(function (Get $get) {
                                        $lt = ((float) ($get('price') ?? 0))
                                            * ((float) ($get('qty') ?? 0));
                                        return new HtmlString(
                                            '<span class="text-base font-bold text-emerald-700 whitespace-nowrap">Rs '
                                            . number_format($lt, 2) . '</span>'
                                        );
                                    })
                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                // hidden snapshots
                                Forms\Components\Hidden::make('sku'),
                                Forms\Components\Hidden::make('is_grocery_request'),
                            ]),
                    ]),

                /* --- Notes --- */
                Forms\Components\Section::make('Notes')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('customer_note')
                            ->label('Customer note (visible on share)')
                            ->rows(2),
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal admin notes')
                            ->rows(2),
                    ]),
            ]),

            /* ===== RIGHT COLUMN (1/3 width) ===== */
            Forms\Components\Group::make()->columnSpan(1)->schema([

                /* --- Delivery --- */
                Forms\Components\Section::make('Delivery')
                    ->schema([
                        Forms\Components\Select::make('area_id')
                            ->label('Area')
                            ->options(
                                fn () => Area::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    $set('delivery_charge', 0);
                                    return;
                                }
                                $area = Area::find($state);
                                if ($area) $set('delivery_charge', (float) $area->delivery_charge);

                                // Auto-suggest an ambassador covering this area
                                $amb = Ambassador::where('is_active', true)
                                    ->where('area_id', $state)
                                    ->orderBy('id')
                                    ->first();
                                if ($amb) $set('ambassador_id', $amb->id);
                            }),

                        Forms\Components\TextInput::make('delivery_charge')
                            ->numeric()->prefix('Rs')->default(0)->minValue(0)->step(0.01)
                            ->helperText('Auto-filled from area; you can override.')
                            ->live(onBlur: true),

                        Forms\Components\Select::make('ambassador_id')
                            ->label('Assigned ambassador')
                            ->options(
                                fn () => Ambassador::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Ambassador $a) => [
                                        $a->id => $a->name . ($a->area ? ' — ' . $a->area->name : ''),
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->placeholder('— Auto by area, or pick one —')
                            ->helperText('Used for commission. Auto-suggested when area is set.'),
                    ]),

                /* --- Coupon --- */
                Forms\Components\Section::make('Coupon')
                    ->schema([
                        Forms\Components\Select::make('coupon_id')
                            ->label('Apply coupon')
                            ->options(
                                fn () => Coupon::query()
                                    ->available()
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn (Coupon $c) => [
                                        $c->id => "{$c->code} — {$c->value_label}",
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('coupon_code', $state ? Coupon::find($state)?->code : null);
                            })
                            ->placeholder('— No coupon —'),

                        Forms\Components\Hidden::make('coupon_code'),
                    ]),

                /* --- Summary --- */
                Forms\Components\Section::make('Summary')
                    ->schema([
                        Forms\Components\Placeholder::make('subtotal_display')
                            ->label('Subtotal')
                            ->content(function (Get $get) {
                                return new HtmlString(self::moneyHtml(self::computeSubtotal($get)));
                            }),

                        Forms\Components\Placeholder::make('discount_display')
                            ->label('Discount')
                            ->content(function (Get $get) {
                                $sub = self::computeSubtotal($get);
                                $d = self::computeDiscount($get, $sub);
                                $color = $d > 0 ? 'text-emerald-600' : 'text-gray-500';
                                return new HtmlString(
                                    '<span class="' . $color . '">- Rs ' . number_format($d, 2) . '</span>'
                                );
                            }),

                        Forms\Components\Placeholder::make('delivery_display')
                            ->label('Delivery')
                            ->content(function (Get $get) {
                                $dc = (float) ($get('delivery_charge') ?? 0);
                                return new HtmlString(self::moneyHtml($dc));
                            }),

                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total')
                            ->content(function (Get $get) {
                                $sub = self::computeSubtotal($get);
                                $d   = self::computeDiscount($get, $sub);
                                $dc  = (float) ($get('delivery_charge') ?? 0);
                                $t   = max(0, $sub - $d + $dc);
                                return new HtmlString(
                                    '<span class="text-lg font-bold text-emerald-700">Rs '
                                    . number_format($t, 2) . '</span>'
                                );
                            }),
                    ]),

                /* --- Status & payment --- */
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(Order::STATUSES)
                            ->default(Order::STATUS_PENDING)
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending'  => 'Pending',
                                'paid'     => 'Paid',
                                'failed'   => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cod'      => 'Cash on Delivery',
                                'cash'     => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'other'    => 'Other',
                            ])
                            ->default('cod')
                            ->required(),
                    ]),
            ]),
        ])->columns(3);
    }

    /* ---------- form math helpers ---------- */

    protected static function computeSubtotal(Get $get): float
    {
        $sum = 0.0;
        foreach (($get('items') ?? []) as $row) {
            $sum += ((float) ($row['price'] ?? 0)) * ((float) ($row['qty'] ?? 0));
        }
        return round($sum, 2);
    }

    protected static function computeDiscount(Get $get, float $subtotal): float
    {
        $cId = $get('coupon_id');
        if (! $cId) return 0.0;
        $coupon = Coupon::find($cId);
        if (! $coupon) return 0.0;

        $result = $coupon->validateAgainst($subtotal);
        return $result['ok'] ? (float) $result['discount'] : 0.0;
    }

    protected static function moneyHtml(float $amt): string
    {
        return '<span class="font-medium">Rs ' . number_format($amt, 2) . '</span>';
    }

    /* ---------- table ---------- */

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                /* ===== Required sequence: order_no → area → total → contact ===== */
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Order #')
                    ->badge()->color('gray')
                    ->searchable()->sortable()->weight('semibold'),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->placeholder('—')
                    ->badge()->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total bill')
                    ->money('PKR')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Contact')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->searchable()
                    ->weight('medium'),

                /* ===== Remaining columns ===== */
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(['customer_name', 'customer_phone'])
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ambassador.name')
                    ->label('Ambassador')
                    ->placeholder('—')
                    ->badge()->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Order $r) => $r->statusColor())
                    ->formatStateUsing(fn (Order $r) => $r->statusLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('pricing_status')
                    ->label('Pricing')
                    ->state(fn (Order $r) => $r->needsPricing() ? 'Needs pricing' : 'Priced')
                    ->badge()
                    ->color(fn ($state) => $state === 'Needs pricing' ? 'warning' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid'     => 'success',
                        'pending'  => 'warning',
                        'failed'   => 'danger',
                        'refunded' => 'gray',
                        default    => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('coupon_code')
                    ->label('Coupon')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime('M j, Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Order::STATUSES),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending'  => 'Pending',
                        'paid'     => 'Paid',
                        'failed'   => 'Failed',
                        'refunded' => 'Refunded',
                    ]),

                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(
                        fn () => \App\Models\Area::query()
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                    ),

                Tables\Filters\SelectFilter::make('ambassador_id')
                    ->label('Ambassador')
                    ->options(
                        fn () => \App\Models\Ambassador::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                    ),

                Tables\Filters\Filter::make('today')
                    ->label('Placed today')
                    ->query(fn (Builder $q) => $q->whereDate('created_at', today())),
            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                /* ===== Required sequence: view → copy text → confirm ===== */
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Order $r) => 'Order ' . $r->order_no)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('xl')
                    ->modalContent(fn (Order $record) =>
                        view('filament.orders.view-modal', ['order' => $record->load('items', 'area', 'ambassador')])
                    ),

                Tables\Actions\Action::make('copy_text')
                    ->label('Copy text')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->modalHeading(fn (Order $r) => 'Order ' . $r->order_no . ' — share text')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('lg')
                    ->modalContent(fn (Order $record) =>
                        view('filament.orders.share-modal', ['order' => $record])
                    ),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $r) => $r->status === Order::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Order $r) => $r->needsPricing()
                        ? 'This order has unpriced grocery items. Confirm anyway?'
                        : 'Confirm this order?')
                    ->action(function (Order $record) {
                        $record->changeStatus(Order::STATUS_CONFIRMED);
                        Notification::make()->title('Order confirmed')->success()->send();
                    }),

                /* ===== Standard actions afterwards ===== */
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
        // Keep this dead simple. Filament adds eager loading for dot-notation
        // columns automatically. Returning a hand-modified Builder with .with()
        // here was the culprit behind the "newQueryWithoutRelationships() on null"
        // error during Livewire tab clicks.
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            OrderResource\RelationManagers\StatusLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    /* Pending orders count badge in nav */
    public static function getNavigationBadge(): ?string
    {
        $count = Order::whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
