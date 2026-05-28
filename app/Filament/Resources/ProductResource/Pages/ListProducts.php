<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('copy_products')
                ->label('Copy products')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->modalHeading('Product list — grouped by category')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('lg')
                ->modalContent(fn () => view('filament.products.copy-list-modal', [
                    'categories' => Category::query()
                        ->where('is_active', true)
                        ->with(['products' => fn ($q) => $q
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')])
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get(),
                ])),

            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->streamCsv()),
        ];
    }

    protected function streamCsv()
    {
        $filename = 'products_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'sku', 'name', 'category', 'unit', 'price', 'compare_price',
                'stock_qty', 'is_active', 'is_featured',
            ]);

            Product::with('category')->orderByDesc('id')->lazy(500)
                ->each(function (Product $p) use ($out) {
                    fputcsv($out, [
                        $p->sku,
                        $p->name,
                        $p->category?->name,
                        $p->unit,
                        number_format((float) $p->price, 2, '.', ''),
                        $p->compare_price ? number_format((float) $p->compare_price, 2, '.', '') : '',
                        $p->stock_qty,
                        $p->is_active ? '1' : '0',
                        $p->is_featured ? '1' : '0',
                    ]);
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
