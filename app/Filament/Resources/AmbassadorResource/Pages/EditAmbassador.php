<?php

namespace App\Filament\Resources\AmbassadorResource\Pages;

use App\Filament\Resources\AmbassadorResource;
use App\Models\StockItem;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAmbassador extends EditRecord
{
    protected static string $resource = AmbassadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_stock')
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
                                ->pluck('name', 'id')->all()
                        )
                        ->searchable(),

                    Forms\Components\TextInput::make('qty')
                        ->numeric()->required()
                        ->minValue(0.001)
                        ->step(0.001),

                    Forms\Components\Textarea::make('note')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->recordStockMovement(
                        stockItemId: (int) $data['stock_item_id'],
                        type: StockMovement::TYPE_ASSIGN,
                        qty: (float) $data['qty'],
                        note: $data['note'] ?? null,
                    );
                    Notification::make()
                        ->title('Stock assigned')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('release_stock')
                ->label('Release stock')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('stock_item_id')
                        ->label('Stock item')
                        ->required()
                        ->options(function () {
                            // Only show items the ambassador currently holds
                            return $this->record->stockBalances()
                                ->with('stockItem')
                                ->where('qty', '>', 0)
                                ->get()
                                ->mapWithKeys(fn ($r) => [
                                    $r->stock_item_id => $r->stockItem->name
                                        . ' (have: ' . rtrim(rtrim((string) $r->qty, '0'), '.')
                                        . ' ' . $r->stockItem->unit . ')',
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->placeholder('— Select item with stock —'),

                    Forms\Components\TextInput::make('qty')
                        ->numeric()->required()
                        ->minValue(0.001)
                        ->step(0.001),

                    Forms\Components\Textarea::make('note')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->recordStockMovement(
                        stockItemId: (int) $data['stock_item_id'],
                        type: StockMovement::TYPE_RELEASE,
                        qty: (float) $data['qty'],
                        note: $data['note'] ?? null,
                    );
                    Notification::make()
                        ->title('Stock released')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
