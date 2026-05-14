<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon  = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Purchases';
    protected static ?int    $navigationSort  = 3;

    protected static ?string $recordTitleAttribute = 'purchase_no';

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* ===== LEFT (2/3) ===== */
            Forms\Components\Group::make()->columnSpan(2)->schema([

                Forms\Components\Section::make('Vendor & date')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->required()
                            ->searchable()
                            ->options(
                                fn () => Vendor::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->getSearchResultsUsing(
                                fn (string $search): array => Vendor::query()
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => Vendor::find($value)?->name)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(150),
                                Forms\Components\TextInput::make('contact_person')->maxLength(120),
                                Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                                Forms\Components\TextInput::make('supplies')->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => Vendor::create($data + ['is_active' => true])->getKey()),

                        Forms\Components\DatePicker::make('purchase_date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                    ]),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->columns(12)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->addActionLabel('+ Add item')
                            ->live()
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->required()
                                    ->maxLength(180)
                                    ->columnSpan(5),

                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->step(0.001)
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('unit')
                                    ->options([
                                        'piece' => 'piece',
                                        'pack'  => 'pack',
                                        'box'   => 'box',
                                        'g'     => 'g',
                                        'kg'    => 'kg',
                                        'ml'    => 'ml',
                                        'l'     => 'l',
                                        'dozen' => 'dozen',
                                    ])
                                    ->default('kg')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('cost_price')
                                    ->numeric()
                                    ->prefix('Rs')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                Forms\Components\Placeholder::make('line_total')
                                    ->label('Line total')
                                    ->content(function (Get $get) {
                                        $lt = ((float) ($get('cost_price') ?? 0))
                                            * ((float) ($get('qty') ?? 0));
                                        return new HtmlString(
                                            '<span class="font-semibold">Rs '
                                            . number_format($lt, 2) . '</span>'
                                        );
                                    })
                                    ->columnSpan(2),
                            ]),
                    ]),

                Forms\Components\Section::make('Notes & invoice')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('invoice_image')
                            ->label('Invoice photo')
                            ->image()
                            ->imageEditor()
                            ->directory('vendors/invoices')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]),

            /* ===== RIGHT (1/3) ===== */
            Forms\Components\Group::make()->columnSpan(1)->schema([

                Forms\Components\Section::make('Summary')
                    ->schema([
                        Forms\Components\Placeholder::make('subtotal_display')
                            ->label('Subtotal')
                            ->content(fn (Get $get) => new HtmlString(
                                self::moneyHtml(self::computeSubtotal($get))
                            )),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax / extra')
                            ->numeric()
                            ->prefix('Rs')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true),

                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total')
                            ->content(function (Get $get) {
                                $sub = self::computeSubtotal($get);
                                $tax = (float) ($get('tax_amount') ?? 0);
                                $total = $sub + $tax;
                                return new HtmlString(
                                    '<span class="text-lg font-bold text-emerald-700">Rs '
                                    . number_format($total, 2) . '</span>'
                                );
                            }),
                    ]),

                Forms\Components\Section::make('Payment')
                    ->schema([
                        Forms\Components\TextInput::make('paid_amount')
                            ->numeric()
                            ->prefix('Rs')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->helperText('Status auto-updates: 0 = Unpaid, partial = Partial, full = Paid.'),

                        Forms\Components\Placeholder::make('balance_display')
                            ->label('Balance due')
                            ->content(function (Get $get) {
                                $sub = self::computeSubtotal($get);
                                $tax = (float) ($get('tax_amount') ?? 0);
                                $paid = (float) ($get('paid_amount') ?? 0);
                                $bal = max(0, $sub + $tax - $paid);
                                $color = $bal > 0 ? 'text-red-600' : 'text-emerald-600';
                                return new HtmlString(
                                    '<span class="' . $color . ' font-semibold">Rs '
                                    . number_format($bal, 2) . '</span>'
                                );
                            }),

                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash'     => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'cheque'   => 'Cheque',
                                'other'    => 'Other',
                            ])
                            ->default('cash')
                            ->required(),
                    ]),
            ]),
        ])->columns(3);
    }

    /* ---------- helpers ---------- */

    protected static function computeSubtotal(Get $get): float
    {
        $sum = 0.0;
        foreach (($get('items') ?? []) as $row) {
            $sum += ((float) ($row['cost_price'] ?? 0)) * ((float) ($row['qty'] ?? 0));
        }
        return round($sum, 2);
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
                Tables\Columns\TextColumn::make('purchase_no')
                    ->label('Purchase #')
                    ->badge()->color('gray')
                    ->searchable()->sortable()->weight('semibold'),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('PKR')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('PKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->state(fn (Purchase $r) => max(0, (float) $r->total - (float) $r->paid_amount))
                    ->money('PKR')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (Purchase $r) => $r->paymentStatusColor())
                    ->formatStateUsing(fn (Purchase $r) => $r->paymentStatusLabel())
                    ->sortable(),

                Tables\Columns\ImageColumn::make('invoice_image')
                    ->label('Invoice')
                    ->size(40)
                    ->square()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(Purchase::PAYMENT_STATUSES),

                Tables\Filters\Filter::make('this_month')
                    ->label('This month')
                    ->query(fn (Builder $query) => $query
                        ->whereBetween('purchase_date', [now()->startOfMonth(), now()->endOfMonth()])),

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
            ->with(['vendor'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit'   => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }

    /* Outstanding-balance count badge */
    public static function getNavigationBadge(): ?string
    {
        $count = Purchase::whereIn('payment_status', ['unpaid', 'partial'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
