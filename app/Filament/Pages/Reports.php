<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Reports\Widgets\CustomerTypeTable;
use App\Filament\Pages\Reports\Widgets\DailyOrdersChart;
use App\Filament\Pages\Reports\Widgets\HighMarginTable;
use App\Filament\Pages\Reports\Widgets\InactiveCustomersTable;
use App\Filament\Pages\Reports\Widgets\OrdersByTimeRangeTable;
use App\Filament\Pages\Reports\Widgets\PeakOrderTimeChart;
use App\Filament\Pages\Reports\Widgets\PendingProductsTable;
use App\Filament\Pages\Reports\Widgets\RevenueByAreaTable;
use App\Filament\Pages\Reports\Widgets\RevenueByProductTable;
use App\Filament\Pages\Reports\Widgets\SaleReportTable;
use App\Filament\Pages\Reports\Widgets\SlowMoversTable;
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
            /* === Sales === */
            DailyOrdersChart::class,
            PeakOrderTimeChart::class,
            OrdersByTimeRangeTable::class,
            SaleReportTable::class,
            CustomerTypeTable::class,
            InactiveCustomersTable::class,
            RevenueByAreaTable::class,
            RevenueByProductTable::class,

            /* === Product === */
            TopProductsTable::class,
            PendingProductsTable::class,
            SlowMoversTable::class,
            HighMarginTable::class,

            /* === Customer === */
            TopCustomersTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
