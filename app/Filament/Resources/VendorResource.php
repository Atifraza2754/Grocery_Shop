<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Vendors';
    protected static ?int    $navigationSort  = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vendor details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('contact_person')
                        ->maxLength(120),

                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('supplies')
                        ->label('Supplies')
                        ->maxLength(255)
                        ->helperText('What this vendor supplies — e.g. "Vegetables, Spices"')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('address')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
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
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Vendor $r) => $r->contact_person),

                Tables\Columns\TextColumn::make('phone')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplies')
                    ->wrap()
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('purchases_count')
                    ->counts('purchases')
                    ->label('Purchases')
                    ->badge()
                    ->color('info')
                    ->numeric(),

                Tables\Columns\TextColumn::make('purchases_total')
                    ->label('Total spent')
                    ->state(fn (Vendor $r) => (float) $r->purchases()->sum('total'))
                    ->money('PKR'),

                Tables\Columns\TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->state(fn (Vendor $r) => max(
                        0,
                        (float) $r->purchases()->sum('total')
                            - (float) $r->purchases()->sum('paid_amount')
                    ))
                    ->money('PKR')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('semibold'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit'   => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
