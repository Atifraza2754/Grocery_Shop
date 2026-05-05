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

        // products keyed by category id for the tab grid
        $productsByCategory = [];
        foreach ($categories as $cat) {
            $productsByCategory[$cat->id] = Product::query()
                ->where('is_active', true)
                ->where('category_id', $cat->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(40)
                ->get();
        }

        return view('site.home', compact('categories', 'featured', 'productsByCategory'));
    }
}
