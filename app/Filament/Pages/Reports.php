<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Reports\Widgets\CustomerRetentionStats;
use App\Filament\Pages\Reports\Widgets\DailyOrdersChart;
use App\Filament\Pages\Reports\Widgets\NewVsReturningChart;
use App\Filament\Pages\Reports\Widgets\SalesOverviewStats;
use App\Filament\Pages\Reports\Widgets\TopCustomersTable;
use App\Filament\Pages\Reports\Widgets\TopProductsTable;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Reports';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.pages.reports';

    public function getHeaderWidgets(): array
    {
        return [
            SalesOverviewStats::class,
            CustomerRetentionStats::class,
            DailyOrdersChart::class,
            NewVsReturningChart::class,
            TopProductsTable::class,
            TopCustomersTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
