<?php

namespace App\Filament\Resources\AmbassadorResource\Pages;

use App\Filament\Resources\AmbassadorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmbassadors extends ListRecords
{
    protected static string $resource = AmbassadorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('+ New ambassador')];
    }
}
