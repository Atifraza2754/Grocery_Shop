<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ New order'),

            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->streamCsv()),
        ];
    }

    protected function streamCsv()
    {
        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'order_no', 'placed_at', 'customer_name', 'customer_phone',
                'area', 'ambassador', 'subtotal', 'discount', 'delivery_charge',
                'total', 'status', 'payment_status', 'payment_method', 'coupon_code',
            ]);

            Order::with(['area', 'ambassador'])->orderByDesc('id')->lazy(500)
                ->each(function (Order $o) use ($out) {
                    fputcsv($out, [
                        $o->order_no,
                        $o->created_at?->format('Y-m-d H:i:s'),
                        $o->customer_name,
                        $o->customer_phone,
                        $o->area?->name,
                        $o->ambassador?->name,
                        number_format((float) $o->subtotal, 2, '.', ''),
                        number_format((float) $o->discount, 2, '.', ''),
                        number_format((float) $o->delivery_charge, 2, '.', ''),
                        number_format((float) $o->total, 2, '.', ''),
                        $o->status,
                        $o->payment_status,
                        $o->payment_method,
                        $o->coupon_code,
                    ]);
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * IMPORTANT: closure parameter MUST be named `$query` — that's what Filament
     * injects via `['query' => $query]`. If you name it `$q`, Filament's container
     * falls back to creating a fresh model-less Builder, which crashes downstream
     * with "newQueryWithoutRelationships() on null".
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'pending' => Tab::make('Pending')
                ->badge(fn () => Order::where('status', Order::STATUS_PENDING)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_PENDING)),

            'confirmed' => Tab::make('Confirmed')
                ->badge(fn () => Order::where('status', Order::STATUS_CONFIRMED)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_CONFIRMED)),

            'preparing' => Tab::make('Preparing')
                ->badge(fn () => Order::where('status', Order::STATUS_PREPARING)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_PREPARING)),

            'out_for_delivery' => Tab::make('Out for delivery')
                ->badge(fn () => Order::where('status', Order::STATUS_OUT_FOR_DELIVERY)->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_OUT_FOR_DELIVERY)),

            'delivered' => Tab::make('Delivered')
                ->badge(fn () => Order::where('status', Order::STATUS_DELIVERED)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_DELIVERED)),

            'cancelled' => Tab::make('Cancelled')
                ->badge(fn () => Order::where('status', Order::STATUS_CANCELLED)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_CANCELLED)),
        ];
    }
}
