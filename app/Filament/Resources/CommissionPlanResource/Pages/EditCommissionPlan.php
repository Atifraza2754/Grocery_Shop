<?php

namespace App\Filament\Resources\CommissionPlanResource\Pages;

use App\Filament\Resources\CommissionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommissionPlan extends EditRecord
{
    protected static string $resource = CommissionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
