<?php

namespace App\Filament\Resources\AmbassadorResource\Pages;

use App\Filament\Resources\AmbassadorResource;
use App\Models\Commission;
use App\Models\CommissionPlan;
use App\Models\StockItem;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditAmbassador extends EditRecord
{
    protected static string $resource = AmbassadorResource::class;

    protected function getHeaderActions(): array
    {
        return [

            /* ====================== ASSIGN STOCK ====================== */
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
                ->action(function (array $data) {
                    $this->record->recordStockMovement(
                        stockItemId: (int) $data['stock_item_id'],
                        type: StockMovement::TYPE_ASSIGN,
                        qty: (float) $data['qty'],
                        note: $data['note'] ?? null,
                    );
                    Notification::make()->title('Stock assigned')->success()->send();
                }),

            /* ====================== RELEASE STOCK ====================== */
            Actions\Action::make('release_stock')
                ->label('Release stock')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalDescription('Releasing stock means it has been sold/distributed. The system creates a commission entry based on the chosen plan.')
                ->form([
                    Forms\Components\Select::make('stock_item_id')
                        ->label('Stock item')
                        ->required()
                        ->options(function () {
                            return $this->record->stockBalances()
                                ->with('stockItem')
                                ->where('qty', '>', 0)
                                ->get()
                                ->mapWithKeys(fn ($r) => [
                                    $r->stock_item_id => $r->stockItem->name
                                        . ' (have: ' . rtrim(rtrim((string) $r->qty, '0'), '.')
                                        . ' ' . $r->stockItem->unit
                                        . ' @ Rs ' . number_format((float) $r->stockItem->price, 0) . ')',
                                ])
                                ->all();
                        })
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
                        ->default(fn () => $this->record->plan_id)
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
                ->action(function (array $data) {
                    $movement = $this->record->recordStockMovement(
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
                            'ambassador_id'     => $this->record->id,
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

            /* ====================== PAY OUT ====================== */
            Actions\Action::make('pay_out')
                ->label('Pay out')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->visible(fn () => $this->record->commission_pending > 0)
                ->modalHeading(fn () => 'Pay '
                    . $this->record->name
                    . ' — total remaining Rs '
                    . number_format($this->record->commission_pending, 2))
                ->modalDescription('Enter the amount you are paying now. Partial payments are allowed; oldest commissions are settled first.')
                ->form([
                    Forms\Components\TextInput::make('pay_amount')
                        ->label('Pay amount (Rs)')
                        ->numeric()
                        ->required()
                        ->prefix('Rs')
                        ->minValue(0.01)
                        ->step(0.01),

                    Forms\Components\Select::make('paid_method')
                        ->label('Method')
                        ->options([
                            'cash'     => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'mobile'   => 'JazzCash / EasyPaisa',
                            'other'    => 'Other',
                        ])
                        ->default('cash')
                        ->required(),

                    Forms\Components\Textarea::make('note')->rows(2),
                ])
                ->action(function (array $data) {
                    $applied = Commission::applyAmbassadorPayment(
                        $this->record->id,
                        (float) $data['pay_amount'],
                        $data['paid_method'] ?? null,
                        $data['note'] ?? null,
                    );

                    Notification::make()
                        ->title('Paid out Rs ' . number_format($applied, 2))
                        ->body('Remaining: Rs ' . number_format($this->record->fresh()->commission_pending, 2))
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
