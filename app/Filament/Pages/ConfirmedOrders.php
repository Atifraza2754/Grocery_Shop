<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ConfirmedOrders extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'PO Print';
    protected static ?int    $navigationSort  = 3;

    protected static ?string $title = 'PO Print';

    protected static string $view = 'filament.pages.confirmed-orders';

    /**
     * Which tab is active. 'order_products' is the default view (all
     * normal categories). 'product_items' shows only the special
     * deal/combo categories with their included items listed.
     */
    public string $activeTab = 'order_products';

    /**
     * Categories shown ONLY in the "Product Items" tab (and excluded
     * from the "Order Products" tab). Matched case-insensitively against
     * the category name, with a few common spelling variants.
     */
    protected const PRODUCT_ITEM_CATEGORIES = [
        'precut veggies',
        'pre-cut veggies',
        'precut vegetables',
        'pre-cut vegetables',
        'mix veggies',
        'mix vegetables',
        'deal',
        'deals',
        'pre-cut deals',
        'precut deals',
    ];

    /** Show a breadcrumb trail like the other admin pages. */
    public function getBreadcrumbs(): array
    {
        return [
            'Sales',
            'PO Print',
        ];
    }

    /**
     * Rebuild the table the instant the tab changes so it switches on the
     * SAME click. (Filament builds the table during boot using the previous
     * value, which otherwise makes the table lag one click behind.)
     */
    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->bootedInteractsWithTable();
    }

    /** True when a category belongs in the "Product Items" tab. */
    protected function isProductItemCategory(Category $category): bool
    {
        $name = strtolower(trim((string) $category->name));

        return in_array($name, self::PRODUCT_ITEM_CATEGORIES, true);
    }

    /** Format a decimal quantity without trailing zeros (e.g. 1.000 -> "1"). */
    protected function formatQty($qty): string
    {
        return rtrim(rtrim(number_format((float) $qty, 3, '.', ''), '0'), '.');
    }

    /** "1 kg", "5 pao", etc. */
    protected function qtyWithUnit($qty, ?string $unit): string
    {
        return trim($this->formatQty($qty) . ' ' . ($unit ?? ''));
    }

    /**
     * Generate the Cash Memo PDF for the records in the active tab.
     * One memo per order PER category, 4 memos to an A4 page, across as
     * many pages as needed. Triggered by the "Generate Confirmed Orders"
     * button.
     */
    public function generate()
    {
        $isItemsTab = $this->activeTab === 'product_items';

        $allCategories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $specialIds = $allCategories
            ->filter(fn (Category $category): bool => $this->isProductItemCategory($category))
            ->pluck('id')
            ->all();

        $tabCategories = $allCategories
            ->filter(fn (Category $category): bool => $this->isProductItemCategory($category) === $isItemsTab)
            ->values();

        // Same filtering as the on-screen table, but un-paginated: every
        // matching confirmed order, oldest first.
        $query = Order::query()
            ->where('status', Order::STATUS_CONFIRMED)
            ->with(['items.product.items', 'items.category'])
            ->orderBy('created_at', 'asc');

        if ($isItemsTab) {
            $query->whereHas(
                'items.product',
                fn ($pq) => $pq->whereIn('category_id', $specialIds)
            );
        } elseif (! empty($specialIds)) {
            $query->whereHas('items', function ($iq) use ($specialIds) {
                $iq->whereDoesntHave(
                    'product',
                    fn ($pq) => $pq->whereIn('category_id', $specialIds)
                );
            });
        }

        $orders = $query->get();

        $memos = [];

        // One memo PER category: gather every matching product from ALL
        // confirmed orders into a single memo for that category.
        foreach ($tabCategories as $category) {
            $rows = [];
            $sno  = 1;

            foreach ($orders as $order) {
                $catItems = $order->items
                    ->filter(fn (OrderItem $item) => $item->resolvedCategoryId() === $category->id)
                    ->values();

                foreach ($catItems as $item) {
                    // The ordered product / deal.
                    $rows[] = [
                        'sno'   => $sno++,
                        'desc'  => $item->name,
                        'qty'   => $this->qtyWithUnit($item->qty, $item->unit),
                        'price' => number_format((float) $item->price, 2),
                        'sub'   => false,
                    ];

                    // On the Product Items tab, list the deal's included
                    // items underneath (no individual price).
                    if ($isItemsTab) {
                        foreach ($item->product?->items ?? [] as $pi) {
                            /** @var ProductItem $pi */
                            $rows[] = [
                                'sno'   => '',
                                'desc'  => $pi->item_name,
                                'qty'   => $this->qtyWithUnit($pi->qty, $pi->unit),
                                'price' => '',
                                'sub'   => true,
                            ];
                        }
                    }
                }
            }

            if (empty($rows)) {
                continue;
            }

            $memos[] = [
                'category' => $category->name,
                'rows'     => $rows,
            ];
        }

        if (empty($memos)) {
            Notification::make()
                ->title('No records to generate in this tab.')
                ->warning()
                ->send();

            return;
        }

        $pdf = Pdf::loadView('pdf.confirmed-order-memos', [
            'memos' => $memos,
        ])->setPaper('a4', 'portrait');

        $filename = 'cash-memos-'
            . ($isItemsTab ? 'product-items' : 'order-products')
            . '-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function table(Table $table): Table
    {
        $isItemsTab = $this->activeTab === 'product_items';

        // All categories, then split by which tab they belong to. New ones
        // added later appear automatically (ordered by sort_order, name).
        $allCategories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // IDs of the special "Product Items" categories (Precut Veggies,
        // Mix Veggies, Deal, Pre-cut Deals).
        $specialIds = $allCategories
            ->filter(fn (Category $category): bool => $this->isProductItemCategory($category))
            ->pluck('id')
            ->all();

        $categories = $allCategories
            ->filter(fn (Category $category): bool => $this->isProductItemCategory($category) === $isItemsTab)
            ->values();

        $columns = [
            // Continuous serial number across pages.
            TextColumn::make('__sno')
                ->label('S:No')
                ->rowIndex(),
        ];

        foreach ($categories as $category) {
            $categoryId = $category->id;

            if ($isItemsTab) {
                // Product Items tab: product name header, then its
                // included items (qty unit name) listed underneath.
                $columns[] = TextColumn::make('cat_' . $categoryId)
                    ->label($category->name)
                    ->getStateUsing(function (Order $record) use ($categoryId): array {
                        $lines = [];

                        foreach ($record->items as $item) {
                            if ($item->resolvedCategoryId() !== $categoryId) {
                                continue;
                            }

                            // Header line: the ordered product/deal name.
                            $lines[] = $item->name;

                            // Included items belonging to this product.
                            $included = $item->product?->items ?? collect();

                            foreach ($included as $pi) {
                                /** @var ProductItem $pi */
                                $qty  = $this->formatQty($pi->qty);
                                $unit = $pi->unit ? $pi->unit . ' ' : '';

                                $lines[] = trim($qty . ' ' . $unit . $pi->item_name);
                            }
                        }

                        return $lines;
                    })
                    ->listWithLineBreaks()
                    ->placeholder('');
            } else {
                // Order Products tab: one line per ordered item
                // ("qty unit name"). No wrap -> each product stays on
                // its own clean single line.
                $columns[] = TextColumn::make('cat_' . $categoryId)
                    ->label($category->name)
                    ->getStateUsing(function (Order $record) use ($categoryId): array {
                        return $record->items
                            ->filter(fn (OrderItem $item) => $item->resolvedCategoryId() === $categoryId)
                            ->map(function (OrderItem $item) {
                                $qty  = $this->formatQty($item->qty);
                                $unit = $item->unit ? $item->unit . ' ' : '';

                                return trim($qty . ' ' . $unit . $item->name);
                            })
                            ->values()
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->placeholder('');
            }
        }

        $query = Order::query()
            ->where('status', Order::STATUS_CONFIRMED)
            ->with(['items.product.items', 'items.category']);

        if ($isItemsTab) {
            // Only orders that actually have an item in one of the special
            // (deal/combo) categories — so the first such order is row 1.
            $query->whereHas(
                'items.product',
                fn ($pq) => $pq->whereIn('category_id', $specialIds)
            );
        } elseif (! empty($specialIds)) {
            // Order Products tab: only orders that have at least one item
            // that is NOT a special-category product (normal products and
            // grocery requests stay; deal-only orders drop out here).
            $query->whereHas('items', function ($iq) use ($specialIds) {
                $iq->whereDoesntHave(
                    'product',
                    fn ($pq) => $pq->whereIn('category_id', $specialIds)
                );
            });
        }

        return $table
            ->query($query)
            ->columns($columns)
            ->defaultSort('created_at', 'asc')
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No confirmed orders yet');
    }
}
