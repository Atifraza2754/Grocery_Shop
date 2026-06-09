<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Number of products returned per "page" / Load-more click. */
    public const PAGE_SIZE = 40;

    public function show(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'items', 'images'])
            ->firstOrFail();

        $related = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        // If the request is AJAX (modal), return a partial view without layout
        if (request()->ajax()) {
            return view('site.products._modal', compact('product', 'related'));
        }

        return view('site.products.show', compact('product', 'related'));
    }

    /**
     * Server-side search across ALL active products by name. Returns rendered
     * product-card HTML for the home page to inject. Paginated via offset /
     * PAGE_SIZE so the "Load more" button can fetch the next batch.
     */
    public function ajaxSearch(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $offset = max(0, (int) $request->query('offset', 0));

        // Require at least 2 chars to avoid huge result sets on a single letter.
        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([
                'html'    => '',
                'count'   => 0,
                'hasMore' => false,
            ]);
        }

        // Fetch PAGE_SIZE + 1 to detect "more available" without an extra COUNT.
        $rows = Product::query()
            ->where('is_active', true)
            ->where('name', 'like', '%' . $q . '%')
            ->orderBy('name')
            ->skip($offset)
            ->take(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $rows->count() > self::PAGE_SIZE;
        $rows    = $rows->take(self::PAGE_SIZE);

        $html = '';
        foreach ($rows as $product) {
            $html .= view('site.partials.product-card', ['product' => $product])->render();
        }

        return response()->json([
            'html'    => $html,
            'count'   => $rows->count(),
            'hasMore' => $hasMore,
        ]);
    }

    /**
     * Next batch of products for a single category — drives the per-category
     * "Load more" button on the home page.
     */
    public function ajaxCategory(Category $category, Request $request)
    {
        $offset = max(0, (int) $request->query('offset', 0));

        $rows = Product::query()
            ->where('is_active', true)
            ->where('category_id', $category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->skip($offset)
            ->take(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $rows->count() > self::PAGE_SIZE;
        $rows    = $rows->take(self::PAGE_SIZE);

        $html = '';
        foreach ($rows as $product) {
            $html .= view('site.partials.product-card', ['product' => $product])->render();
        }

        return response()->json([
            'html'    => $html,
            'count'   => $rows->count(),
            'hasMore' => $hasMore,
        ]);
    }
}
