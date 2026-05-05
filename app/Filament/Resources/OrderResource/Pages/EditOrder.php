<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction('confirm',          'Confirm order',     Order::STATUS_CONFIRMED,        'heroicon-o-check-circle',           'info',     [Order::STATUS_PENDING]),
            $this->statusAction('start_preparing',  'Start preparing',   Order::STATUS_PREPARING,        'heroicon-o-fire',                   'warning',  [Order::STATUS_CONFIRMED]),
            $this->statusAction('out_for_delivery', 'Out for delivery',  Order::STATUS_OUT_FOR_DELIVERY, 'heroicon-o-truck',                  'primary',  [Order::STATUS_PREPARING, Order::STATUS_CONFIRMED]),
            $this->statusAction('mark_delivered',   'Mark delivered',    Order::STATUS_DELIVERED,        'heroicon-o-check-badge',            'success',  [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_PREPARING, Order::STATUS_CONFIRMED]),
            $this->statusAction('cancel',           'Cancel order',      Order::STATUS_CANCELLED,        'heroicon-o-x-circle',               'danger',   [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_OUT_FOR_DELIVERY], requireNote: true),

            Actions\Action::make('copy_text')
                ->label('Copy text')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->modalHeading(fn () => 'Order ' . $this->record->order_no . ' — share text')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('lg')
                ->modalContent(fn () => view('filament.orders.share-modal', [
                    'order' => $this->record,
                ])),

            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Recompute totals after every edit.
     */
    protected function afterSave(): void
    {
        $this->record->refresh()->load(['items', 'coupon', 'area']);
        $this->record->recalculateTotals();
    }

    /**
     * Build a status-change action with confirmation modal + optional note.
     */
    protected function statusAction(
        string $name,
        string $label,
        string $toStatus,
        string $icon,
        string $color,
        array $allowedFrom,
        bool $requireNote = false
    ): Actions\Action {
        return Actions\Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn () => in_array($this->record->status, $allowedFrom, true))
            ->requiresConfirmation()
            ->form([
                Forms\Components\Textarea::make('note')
                    ->label('Note (optional)')
                    ->rows(2)
                    ->required($requireNote),
            ])
            ->action(function (array $data) use ($toStatus, $label) {
                $this->record->changeStatus($toStatus, $data['note'] ?? null);
                Notification::make()
                    ->title("Order {$label}")
                    ->body($this->record->order_no . ' is now ' . $this->record->statusLabel())
                    ->success()
                    ->send();
                $this->refreshFormData(['status', 'confirmed_at', 'delivered_at', 'cancelled_at']);
            });
    }
}
