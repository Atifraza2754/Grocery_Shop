<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $featured = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with('category')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // products keyed by category id for the tab grid. Fetch 41 so we can
        // tell whether a "Load more" button is needed for that category.
        $productsByCategory = [];
        $hasMoreByCategory  = [];
        foreach ($categories as $cat) {
            $list = Product::query()
                ->where('is_active', true)
                ->where('category_id', $cat->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(41)
                ->get();

            $hasMoreByCategory[$cat->id]  = $list->count() > 40;
            $productsByCategory[$cat->id] = $list->take(40);
        }

        return view('site.home', compact('categories', 'featured', 'productsByCategory', 'hasMoreByCategory'));
    }
}
