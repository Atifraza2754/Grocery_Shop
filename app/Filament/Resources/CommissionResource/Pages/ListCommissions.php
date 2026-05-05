<?php

namespace App\Filament\Resources\CommissionResource\Pages;

use App\Filament\Resources\CommissionResource;
use App\Models\Commission;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCommissions extends ListRecords
{
    protected static string $resource = CommissionResource::class;

    public function getTabs(): array
    {
        return [
            'all'       => Tab::make('All'),
            'pending'   => Tab::make('Pending')
                ->badge(Commission::where('status', Commission::STATUS_PENDING)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', Commission::STATUS_PENDING)),
            'paid'      => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', Commission::STATUS_PAID)),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', Commission::STATUS_CANCELLED)),
        ];
    }
}
