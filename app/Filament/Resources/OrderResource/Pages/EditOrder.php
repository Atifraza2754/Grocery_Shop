<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Compact heading — order number with a status pill, on its own line.
     */
    public function getHeading(): string|Htmlable
    {
        $r = $this->record;

        $colorMap = [
            'pending'          => 'background:#f1f3f5;color:#495057;',
            'confirmed'        => 'background:#e0f2fe;color:#0369a1;',
            'preparing'        => 'background:#fff8e1;color:#b27300;',
            'out_for_delivery' => 'background:#ddd6fe;color:#6d28d9;',
            'delivered'        => 'background:#dcfce7;color:#166534;',
            'cancelled'        => 'background:#fdecea;color:#c62828;',
        ];
        $statusStyle = $colorMap[$r->status] ?? 'background:#f1f3f5;color:#495057;';

        return new HtmlString(
            '<span class="text-lg font-semibold tracking-tight text-gray-800 dark:text-gray-100">'
                . 'Edit ' . e($r->order_no)
            . '</span>'
            . '<span class="ml-3 inline-block px-2 py-0.5 rounded-full text-xs font-semibold align-middle" style="' . $statusStyle . '">'
                . e($r->statusLabel())
            . '</span>'
        );
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edit ' . $this->record->order_no;
    }

    /* ---------- Header actions ---------- */

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction(
                'confirm',          'Confirm',
                Order::STATUS_CONFIRMED,        'heroicon-o-check-circle',  'info',
                [Order::STATUS_PENDING],
            ),
            $this->statusAction(
                'start_preparing',  'Start preparing',
                Order::STATUS_PREPARING,        'heroicon-o-fire',          'warning',
                [Order::STATUS_CONFIRMED],
            ),
            $this->statusAction(
                'out_for_delivery', 'Out for delivery',
                Order::STATUS_OUT_FOR_DELIVERY, 'heroicon-o-truck',         'primary',
                [Order::STATUS_PREPARING, Order::STATUS_CONFIRMED],
            ),
            $this->statusAction(
                'mark_delivered',   'Mark delivered',
                Order::STATUS_DELIVERED,        'heroicon-o-check-badge',   'success',
                [
                    Order::STATUS_OUT_FOR_DELIVERY,
                    Order::STATUS_PREPARING,
                    Order::STATUS_CONFIRMED,
                ],
            ),

            // Cancel — explicit definition, separate from forward-only flow.
            // Required note + danger color + dedicated copy.
            Actions\Action::make('cancel')
                ->label('Cancel order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [
                    Order::STATUS_PENDING,
                    Order::STATUS_CONFIRMED,
                    Order::STATUS_PREPARING,
                    Order::STATUS_OUT_FOR_DELIVERY,
                ], true))
                ->modalHeading('Cancel this order?')
                ->modalDescription('Marking the order cancelled cannot be undone. Pending coupon usage will be refunded.')
                ->modalSubmitActionLabel('Yes, cancel order')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('Reason')
                        ->placeholder('Why is this order being cancelled?')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->changeStatus(Order::STATUS_CANCELLED, $data['note'] ?? null);
                    Notification::make()
                        ->title('Order cancelled')
                        ->body($this->record->order_no . ' is now Cancelled')
                        ->success()
                        ->send();
                    $this->refreshFormData(['status', 'cancelled_at']);
                }),

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
     * Forward-only status actions (Confirm / Preparing / Out / Delivered).
     * Cancel is defined inline above because its UX is different.
     */
    protected function statusAction(
        string $name,
        string $label,
        string $toStatus,
        string $icon,
        string $color,
        array $allowedFrom,
    ): Actions\Action {
        return Actions\Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn () => in_array($this->record->status, $allowedFrom, true))
            ->modalHeading(fn () => $label . '?')
            ->modalDescription('Optional note will be saved in the order audit log.')
            ->modalSubmitActionLabel($label)
            ->form([
                Forms\Components\Textarea::make('note')
                    ->label('Note (optional)')
                    ->rows(2),
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
