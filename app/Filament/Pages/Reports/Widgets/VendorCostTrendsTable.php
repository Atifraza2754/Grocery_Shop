<?php

namespace App\Filament\Pages\Reports\Widgets;

use App\Models\Purchase;
use App\Models\Vendor;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Per-vendor monthly purchase totals for the last 6 months.
 * Helps spot rising costs and unpaid balances.
 */
class VendorCostTrendsTable extends BaseWidget
{
    protected static ?string $heading = 'Vendor cost trends (last 6 months)';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Vendor::query()
                    ->select([
                        'vendors.id',
                        'vendors.name',
                        DB::raw('COUNT(purchases.id) as purchases_count'),
                        DB::raw('COALESCE(SUM(purchases.total),0) as total_spent'),
                        DB::raw('COALESCE(SUM(purchases.paid_amount),0) as total_paid'),
                        DB::raw('COALESCE(SUM(purchases.total) - SUM(purchases.paid_amount),0) as balance_due'),
                    ])
                    ->leftJoin('purchases', function ($join) {
                        $join->on('purchases.vendor_id', '=', 'vendors.id')
                            ->where('purchases.purchase_date', '>=', now()->subMonths(6)->startOfMonth());
                    })
                    ->groupBy('vendors.id', 'vendors.name')
                    ->orderByDesc('total_spent')
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Vendor')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('purchases_count')
                    ->label('Purchases')
                    ->numeric()
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('total_spent')
                    ->money('PKR')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Paid')
                    ->money('PKR'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('PKR')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('semibold'),

                /* Per-month breakdown across the last 6 months */
                Tables\Columns\TextColumn::make('monthly_breakdown')
                    ->label('Last 6 months')
                    ->state(function (Vendor $r) {
                        $rows = DB::table('purchases')
                            ->where('vendor_id', $r->id)
                            ->where('purchase_date', '>=', now()->subMonths(6)->startOfMonth())
                            ->selectRaw("DATE_FORMAT(purchase_date, '%Y-%m') as ym, SUM(total) as total")
                            ->groupBy('ym')
                            ->orderBy('ym')
                            ->pluck('total', 'ym');

                        $months = [];
                        for ($i = 5; $i >= 0; $i--) {
                            $key   = now()->subMonths($i)->format('Y-m');
                            $label = now()->subMonths($i)->format('M');
                            $val   = (float) ($rows[$key] ?? 0);
                            $months[] = $label . ': Rs ' . number_format($val, 0);
                        }
                        return implode(' · ', $months);
                    })
                    ->wrap(),
            ]);
    }
}
