<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Snapshot customer fields onto the order before insert,
     * so editing the customer later doesn't change history.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['customer_id'])) {
            $c = Customer::find($data['customer_id']);
            if ($c) {
                $data['customer_name']  = $data['customer_name']  ?: $c->name;
                $data['customer_phone'] = $data['customer_phone'] ?: $c->phone;
                if (empty($data['delivery_address'])) {
                    $data['delivery_address'] = $c->address;
                }
                if (empty($data['area_id']) && $c->area_id) {
                    $data['area_id'] = $c->area_id;
                }
            }
        }
        return $data;
    }

    /**
     * After items are saved (via the relationship() repeater),
     * recompute money fields on the order.
     */
    protected function afterCreate(): void
    {
        $this->record->refresh()->load(['items', 'coupon', 'area']);
        $this->record->recalculateTotals();
    }
}
