<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
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
}
