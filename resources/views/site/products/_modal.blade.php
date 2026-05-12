<style>
    .breadcrumb a { color: var(--gs-muted); text-decoration: none; }
    .breadcrumb a:hover { color: var(--gs-primary); }

    .pd-gallery {
        background: #fff; border: 1px solid var(--gs-border);
        border-radius: 16px; overflow: hidden;
        height: 100%;
    }
    .pd-main-img-wrap {
        height: 380px; background: #f8f9fa;
        display: flex; align-items: center; justify-content: center;
    }
    .pd-main-img { max-height: 360px; max-width: 100%; object-fit: contain; padding: 16px; }
    .pd-thumbs {
        display: flex; gap: 8px; padding: 10px;
        overflow-x: auto; border-top: 1px solid var(--gs-border);
    }
    .pd-thumb {
        height: 64px; width: 64px;
        border-radius: 8px; border: 2px solid transparent;
        background: #f8f9fa; cursor: pointer;
        flex-shrink: 0; padding: 4px;
        display: flex; align-items: center; justify-content: center;
    }
    .pd-thumb.active { border-color: var(--gs-primary); }
    .pd-thumb img { max-height: 100%; max-width: 100%; object-fit: contain; }

    .pd-info { background: #fff; border: 1px solid var(--gs-border); border-radius: 16px; padding: 1.5rem; height: 100%; }
    .pd-cat { color: var(--gs-primary); font-weight: 600; text-decoration: none; font-size: .9rem; }
    .pd-name { font-weight: 700; font-size: 1.6rem; margin: .2rem 0 .35rem; line-height: 1.2; }
    .pd-sku  { color: var(--gs-muted); font-size: .85rem; margin-bottom: 1rem; }

    .pd-price-row {
        display: flex; align-items: baseline; gap: 12px;
        margin-bottom: 1rem; padding-bottom: 1rem;
        border-bottom: 1px dashed var(--gs-border);
    }
    .pd-price {
        color: var(--gs-primary); font-weight: 800; font-size: 1.8rem;
    }
    .pd-price .unit { color: var(--gs-muted); font-size: 1rem; font-weight: 500; }
    .pd-compare { color: #aaa; text-decoration: line-through; font-size: 1rem; }

    .pd-section-title {
        font-weight: 700; font-size: 1rem; margin-bottom: .6rem;
        color: var(--gs-primary-dark);
    }

    .qty-stepper {
        display: inline-flex; align-items: center; border: 1.5px solid var(--gs-border);
        border-radius: 999px; overflow: hidden;
    }
    .qty-stepper button {
        background: #fff; border: none; padding: 8px 14px; font-weight: 700;
        color: var(--gs-primary); cursor: pointer;
    }
    .qty-stepper button:hover { background: var(--gs-primary-soft); }
    .qty-stepper input {
        border: none; width: 56px; text-align: center; font-weight: 700; height: 40px;
        outline: none;
    }

    /* Modal bottom qty / add layout (matches provided screenshot) */
    .modal-qty-row {
        display: flex; align-items: center; gap: 12px; width: 100%;
        max-width: 520px; margin: 0 auto;
    }
    .modal-qty-row .qty-circle {
        width: 44px; height: 44px; border-radius: 999px; background: #fff;
        display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--gs-border);
        font-size: 1.25rem; color: var(--gs-primary-dark);
    }
    .modal-qty-row .qty-circle.btn-plus {
        background: var(--gs-primary); color: #fff; border-color: var(--gs-primary);
    }
    .modal-qty-row .qty-display {
        flex: 1; background: #fff; border-radius: 999px; padding: 10px 16px; text-align: center;
        font-weight: 700; color: #222; border: 1px solid var(--gs-border);
    }
    @media (max-width: 576px) {
        .modal-qty-row { gap: 10px; }
        .modal-qty-row .qty-circle { width: 44px; height: 44px; }
        .modal-qty-row .qty-display { padding: 12px 10px; }
    }

    .items-included {
        background: var(--gs-primary-soft);
        border-radius: 12px; padding: 1rem;
    }
    .items-included li { color: #4d4d4d; }
    .items-included li strong { color: var(--gs-primary-dark); }

    /* Related products card layout copies home page */
    .product-card { border: 1px solid var(--gs-border); border-radius: 14px; background: #fff; height: 100%;
        overflow: hidden; transition: transform .25s, box-shadow .25s;
        text-decoration: none; color: inherit; display: flex; flex-direction: column; }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0,0,0,.08); color: inherit; }
    .product-img-wrap { height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .product-img { max-height: 100%; max-width: 100%; object-fit: contain; padding: 10px; }
    .product-body { padding: .75rem; display: flex; flex-direction: column; flex: 1; }
    .product-title { font-size: .92rem; font-weight: 600; margin-bottom: 4px; line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.4em;}
    .product-price { color: var(--gs-primary); font-weight: 700; font-size: 1rem; margin-bottom: .5rem; }
    .product-price .unit { color: var(--gs-muted); font-size: .75rem; font-weight: 500; }
</style>

<div class="container py-4 product-modal-root" data-product-id="{{ $product->id }}">

    {{-- Breadcrumb (optional in modal) --}}
        <div class="d-flex align-items-center justify-content-center mb-3 position-relative">
            <h4 class="mb-0">Product Details</h4>
            <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

    <div class="row g-4">
        {{-- ============ GALLERY ============ --}}
        <div class="col-lg-6">
            <div class="pd-gallery">
                <div class="pd-main-img-wrap">
                    @php
                        $cover = $product->image ? \Illuminate\Support\Facades\Storage::url($product->image) : null;
                    @endphp

                    @if ($cover)
                        <img src="{{ $cover }}" alt="{{ $product->name }}" class="pd-main-img" id="pdMainImg">
                    @else
                        <i class="fa-solid fa-image" style="font-size:5rem;color:#ccc;"></i>
                    @endif
                </div>

                @if ($product->images->count() || $cover)
                    <div class="pd-thumbs">
                        @if ($cover)
                            <div class="pd-thumb active" data-img="{{ $cover }}">
                                <img src="{{ $cover }}" alt="">
                            </div>
                        @endif
                        @foreach ($product->images as $img)
                            @php $u = \Illuminate\Support\Facades\Storage::url($img->path); @endphp
                            <div class="pd-thumb" data-img="{{ $u }}">
                                <img src="{{ $u }}" alt="">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ INFO ============ --}}
        <div class="col-lg-6">
            <div class="pd-info">
                @if ($product->category)
                    <a href="{{ route('site.home') }}" class="pd-cat">
                        <i class="fa-solid fa-tag"></i> {{ $product->category->name }}
                    </a>
                @endif

                <div class="pd-name">{{ $product->name }}</div>
                <div class="pd-sku">SKU: <strong>{{ $product->sku }}</strong></div>

                <div class="pd-price-row">
                    <div class="pd-price">
                        Rs {{ number_format((float) $product->price, 0) }}
                        <span class="unit">/ {{ $product->unit }}</span>
                    </div>
                    @if ($product->compare_price && $product->compare_price > $product->price)
                        <div class="pd-compare">Rs {{ number_format((float) $product->compare_price, 0) }}</div>
                    @endif

                    @if ($product->stock_qty <= 0)
                        <span class="stock-pill" style="background:#fdecea;color:#c62828;font-size:.8rem;font-weight:600;padding:3px 10px;border-radius:999px;margin-left:auto;">Out of stock</span>
                    @elseif ($product->stock_qty <= ($product->low_stock_threshold ?? 5))
                        <span class="stock-pill" style="background:#fff8e1;color:#b27300;font-size:.8rem;font-weight:600;padding:3px 10px;border-radius:999px;margin-left:auto;">Only {{ $product->stock_qty }} left</span>
                    @else
                        <span class="stock-pill" style="background:#e8f5e9;color:var(--gs-primary-dark);font-size:.8rem;font-weight:600;padding:3px 10px;border-radius:999px;margin-left:auto;">In stock</span>
                    @endif
                </div>

                @if ($product->short_description)
                    <p class="text-muted">{{ $product->short_description }}</p>
                @endif

                {{-- ITEMS INCLUDED --}}
                @if ($product->items->count())
                    <div class="mb-3">
                        <div class="pd-section-title">
                            <i class="fa-solid fa-list-ul me-1"></i>What's inside
                        </div>
                        <div class="items-included">
                            <ul class="mb-0">
                                @foreach ($product->items as $item)
                                    <li>
                                        <strong>{{ rtrim(rtrim((string) $item->qty, '0'), '.') }} {{ $item->unit }}</strong>
                                        — {{ $item->item_name }}
                                        @if ($item->note)
                                            <span class="text-muted small">({{ $item->note }})</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- ADD TO CART --}}
                <div class="my-3">
                    <div class="modal-qty-row">
                        <button class="qty-circle" id="qtyMinus" type="button">−</button>
                        <div class="qty-display" id="qtyDisplay">1 Selected</div>
                        <button class="qty-circle btn-plus" id="qtyPlus" type="button"><i class="fa-solid fa-plus"></i></button>
                        <input type="number" id="qtyInput" value="1" min="1" max="{{ max(1, min($product->low_stock_threshold ?? $product->stock_qty, $product->stock_qty)) }}" style="display:none;">
                    </div>

                       {{-- FULL DESCRIPTION --}}
                        @if ($product->description)
                            <div class="mt-3">
                                <div class="pd-section-title">
                                    <i class="fa-solid fa-circle-info me-1"></i>Product Details
                                </div>
                                <div class="text-muted">{!! $product->description !!}</div>
                            </div>
                        @endif

                    <div class="mt-3">
                        @if ($product->stock_qty > 0)
                            <button class="btn-primary-gs w-100" id="pdAddBtn">
                                <i class="fa-solid fa-basket-shopping me-2"></i> Add to Cart
                            </button>
                        @else
                            <button class="btn-primary-gs w-100" disabled style="opacity:.5;">
                                <i class="fa-solid fa-ban me-2"></i> Unavailable
                            </button>
                        @endif
                    </div>
                </div>

             
            </div>
        </div>
    </div>

    {{-- Related products removed from modal as requested --}}

</div>
