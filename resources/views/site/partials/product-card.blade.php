@php
    /** @var \App\Models\Product $product */
    $imgUrl = $product->image
        ? \Illuminate\Support\Facades\Storage::url($product->image)
        : null;
    $inStock = $product->stock_qty > 0;
    $lowStock = $inStock && $product->stock_qty <= ($product->low_stock_threshold ?? 5);
@endphp

<div class="col-6 col-md-4 col-lg-3"
     data-product
     data-cat="{{ $product->category_id }}"
     data-name="{{ $product->name }}">

    <a href="{{ route('site.product.show', $product->slug) }}" class="product-card">
        <div class="product-img-wrap">
            @if ($imgUrl)
                <img src="{{ $imgUrl }}" alt="{{ $product->name }}" class="product-img">
            @else
                <i class="fa-solid fa-image product-fallback"></i>
            @endif
        </div>

        <div class="product-body">
            @if (!$inStock)
                <span class="stock-pill stock-out">Out of stock</span>
            @elseif ($lowStock)
                <span class="stock-pill stock-low">Low stock</span>
            @else
                <span class="stock-pill stock-in">In stock</span>
            @endif

            <div class="product-title">{{ $product->name }}</div>

            <div class="product-price">
                Rs {{ number_format((float) $product->price, 0) }}
                <span class="unit">/ {{ $product->unit }}</span>
            </div>

            <div class="mt-auto">
                @if ($inStock)
                    <button class="btn-add"
                            type="button"
                            data-add-to-cart="{{ $product->id }}"
                            onclick="event.preventDefault(); event.stopPropagation();">
                        <i class="fa-solid fa-plus me-1"></i> Add to Cart
                    </button>
                @else
                    <button class="btn-add" disabled>
                        <i class="fa-solid fa-ban me-1"></i> Unavailable
                    </button>
                @endif
            </div>
        </div>
    </a>
</div>
