<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Expenses';
    protected static ?string $navigationLabel = 'Expenses';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* ===== LEFT (2/3) ===== */
            Forms\Components\Group::make()->columnSpan(2)->schema([

                Forms\Components\Section::make('Expense details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('expense_category_id')
                            ->label('Expense Category')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(
                                fn () => ExpenseCategory::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => ExpenseCategory::find($value)?->name)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(150),
                                Forms\Components\Textarea::make('description')->rows(2),
                            ])
                            ->createOptionUsing(fn (array $data): int => ExpenseCategory::create($data + ['is_active' => true])->getKey()),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Attachment & notes')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('Attachment (image or PDF)')
                            ->directory('expenses')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'application/pdf',
                            ])
                            ->maxSize(8192)
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]),

            /* ===== RIGHT (1/3) ===== */
            Forms\Components\Group::make()->columnSpan(1)->schema([

                Forms\Components\Section::make('Amount & payment')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('Rs')
                            ->required()
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\Select::make('payment_method')
                            ->options(Expense::PAYMENT_METHODS)
                            ->default('cash')
                            ->required(),

                        Forms\Components\TextInput::make('paid_to')
                            ->label('Paid To')
                            ->maxLength(180),

                        Forms\Components\TextInput::make('bill_no')
                            ->label('Bill No.')
                            ->maxLength(100),
                    ]),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable()
                    ->weight('semibold')
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->money('PKR')
                    ),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (Expense $r) => $r->paymentMethodLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_to')
                    ->label('Paid To')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Expense Category')
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('expense_date')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('expense_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('expense_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . \Illuminate\Support\Carbon::parse($data['from'])->toFormattedDateString();
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until ' . \Illuminate\Support\Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No expenses yet');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
