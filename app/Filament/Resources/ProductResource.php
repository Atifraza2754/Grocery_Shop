<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon  = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int    $navigationSort  = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Forms\Components\Section::make('Basic information')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name', fn ($query) => $query->where('is_active', true)->orderBy('name'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->helperText('SKU prefix is taken from this category.')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated on save')
                                    ->helperText('Auto-generated as {PREFIX}-0001 etc.')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(180)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('short_description')
                                    ->rows(2)
                                    ->maxLength(255)
                                    ->helperText('Short tagline shown in product cards.')
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold', 'italic', 'underline', 'strike',
                                        'bulletList', 'orderedList', 'link', 'h2', 'h3',
                                    ]),
                            ]),

                        Forms\Components\Section::make('Pricing & stock')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('Rs')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01),

                                Forms\Components\TextInput::make('compare_price')
                                    ->numeric()
                                    ->prefix('Rs')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Optional MRP / strikethrough.'),

                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Cost price')
                                    ->numeric()
                                    ->prefix('Rs')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Used to calculate margin.'),

                                Forms\Components\Select::make('unit')
                                    ->options([
                                        'piece' => 'Piece',
                                        'pack'  => 'Pack',
                                        'dozen' => 'Dozen',
                                        'kg'    => 'Kilogram (kg)',
                                        'g'     => 'Gram (g)',
                                        'l'     => 'Litre (l)',
                                        'ml'    => 'Millilitre (ml)',
                                    ])
                                    ->default('piece')
                                    ->required(),

                                Forms\Components\TextInput::make('stock_qty')
                                    ->label('Stock quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\TextInput::make('low_stock_threshold')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Display order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),

                        Forms\Components\Section::make('Items included')
                            ->description('Useful for pre-cut deals and combo packs. Leave empty for single products.')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->columns(4)
                                    ->reorderableWithButtons()
                                    ->orderColumn('sort_order')
                                    ->defaultItems(0)
                                    ->addActionLabel('+ Add item')
                                    ->schema([
                                        Forms\Components\TextInput::make('item_name')
                                            ->required()
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('qty')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->minValue(0)
                                            ->step(0.001),

                                        Forms\Components\Select::make('unit')
                                            ->options([
                                                'piece' => 'piece',
                                                'pack'  => 'pack',
                                                'g'     => 'g',
                                                'kg'    => 'kg',
                                                'ml'    => 'ml',
                                                'l'     => 'l',
                                            ])
                                            ->default('g')
                                            ->required(),

                                        Forms\Components\TextInput::make('note')
                                            ->maxLength(120)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Gallery')
                            ->description('Additional images shown on the product detail page.')
                            ->collapsible()
                            ->schema([
                                Forms\Components\Repeater::make('images')
                                    ->relationship()
                                    ->columns(2)
                                    ->reorderableWithButtons()
                                    ->orderColumn('sort_order')
                                    ->addActionLabel('+ Add image')
                                    ->schema([
                                        Forms\Components\FileUpload::make('path')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('products/gallery')
                                            ->visibility('public')
                                            ->maxSize(2048)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\Section::make('Cover image')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->visibility('public')
                                    ->maxSize(2048),
                            ]),

                        Forms\Components\Section::make('Visibility')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active (in stock)')
                                    ->default(true)
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured on home')
                                    ->default(false)
                                    ->inline(false),
                            ]),
                    ]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->square()
                    ->size(48),

                Tables\Columns\TextColumn::make('sku')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('PKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_qty')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $r): string => match (true) {
                        $r->stock_qty <= 0                 => 'danger',
                        $r->stock_qty <= $r->low_stock_threshold => 'warning',
                        default                            => 'success',
                    }),

                Tables\Columns\TextColumn::make('unit')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_featured'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low / out of stock')
                    ->query(fn (Builder $q) => $q->whereColumn('stock_qty', '<=', 'low_stock_threshold')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            ->with('category')
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    /* Optional: badge with count of low-stock products */
    public static function getNavigationBadge(): ?string
    {
        $count = Product::where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
