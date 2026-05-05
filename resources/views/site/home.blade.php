@extends('site.layouts.app')

@section('title', 'Fresh Groceries Delivered')

@push('styles')
<style>
    /* Hero */
    .hero {
        background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
        border-radius: 20px;
        padding: 2.5rem 2rem;
        margin-bottom: 1.5rem;
    }
    .hero h1 {
        color: var(--gs-primary-dark);
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: .5rem;
    }
    .hero p { color: #4d4d4d; margin-bottom: 0; }
    .hero .badge-tag {
        background: rgba(46,125,50,.12);
        color: var(--gs-primary-dark);
        padding: 4px 12px; border-radius: 999px;
        font-size: .8rem; font-weight: 600;
        display: inline-block; margin-bottom: 8px;
    }

    /* Search */
    .search-container { position: relative; }
    .search-container input {
        border-radius: 999px; padding-left: 42px;
        border: 1px solid #d0d7de;
        height: 46px;
        background: #fff;
    }
    .search-container input:focus {
        border-color: var(--gs-primary);
        box-shadow: 0 0 0 0.2rem rgba(46,125,50,.18);
    }
    .search-icon {
        position: absolute; left: 18px; top: 50%;
        transform: translateY(-50%); color: var(--gs-muted);
    }

    /* Category tabs */
    .category-container {
        display: flex; overflow-x: auto; gap: 10px;
        padding-bottom: 6px;
        -ms-overflow-style: none; scrollbar-width: none;
    }
    .category-container::-webkit-scrollbar { display: none; }

    .category-btn {
        white-space: nowrap;
        border-radius: 999px;
        border: 1px solid #d0d7de;
        background: #fff; color: #495057;
        font-weight: 500; padding: 8px 18px;
        transition: all .25s ease;
    }
    .category-btn.active, .category-btn:hover {
        background: var(--gs-primary); color: #fff; border-color: var(--gs-primary);
    }

    /* Product card */
    .product-card {
        border: 1px solid var(--gs-border);
        border-radius: 14px;
        background: #fff;
        height: 100%;
        overflow: hidden;
        transition: transform .25s ease, box-shadow .25s ease;
        text-decoration: none; color: inherit;
        display: flex; flex-direction: column;
    }
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        color: inherit;
    }
    .product-img-wrap {
        height: 170px;
        background: #f8f9fa;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .product-img {
        max-height: 100%; max-width: 100%;
        object-fit: contain; padding: 12px;
        transition: transform .3s ease;
    }
    .product-card:hover .product-img { transform: scale(1.05); }
    .product-fallback {
        font-size: 3rem; color: #ccc;
    }
    .product-body {
        padding: .85rem; display: flex; flex-direction: column; flex: 1;
    }
    .product-title {
        font-size: 0.95rem; font-weight: 600; margin-bottom: 4px;
        line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.5em;
    }
    .product-price {
        color: var(--gs-primary); font-weight: 700; font-size: 1.05rem;
        margin-bottom: .65rem;
    }
    .product-price .unit { color: var(--gs-muted); font-size: .8rem; font-weight: 500; }
    .stock-pill {
        font-size: .72rem; font-weight: 600;
        padding: 2px 8px; border-radius: 999px;
        margin-bottom: 6px; display: inline-block;
    }
    .stock-in   { background: #e8f5e9; color: var(--gs-primary-dark); }
    .stock-low  { background: #fff8e1; color: #b27300; }
    .stock-out  { background: #fdecea; color: #c62828; }

    .btn-add {
        background: var(--gs-primary); color: #fff;
        border: none; border-radius: 9px;
        padding: 8px 0; font-weight: 600; width: 100%;
        transition: all .2s ease;
    }
    .btn-add:hover  { background: var(--gs-primary-dark); }
    .btn-add:active { transform: scale(.97); }
    .btn-add:disabled { background: #adb5bd; cursor: not-allowed; }

    /* No-results */
    .no-results {
        text-align: center; padding: 50px 20px;
        color: var(--gs-muted); width: 100%;
    }
    .no-results i { font-size: 2.5rem; margin-bottom: .5rem; opacity: .5; }
</style>
@endpush

@section('content')
<div class="container py-4">

    {{-- ============ HERO ============ --}}
    <div class="hero d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <span class="badge-tag"><i class="fa-solid fa-bolt me-1"></i> Fresh & Fast</span>
            <h1 class="display-6">Order Groceries</h1>
            <p class="d-none d-md-block">Pre-cut, frozen, pastes & deals — delivered to your door.</p>
        </div>
        <div class="d-none d-md-block" style="font-size: 4rem; opacity: .25; color: var(--gs-primary-dark);">
            <i class="fa-solid fa-basket-shopping"></i>
        </div>
    </div>

    {{-- ============ SEARCH ============ --}}
    <div class="search-container mb-3">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
    </div>

    {{-- ============ CATEGORY TABS ============ --}}
    <div class="category-container mb-4" id="categoryTabs">
        <button class="category-btn active" data-cat="all">
            <i class="fa-solid fa-grip me-2"></i>All
        </button>
        @foreach ($categories as $cat)
            <button class="category-btn" data-cat="{{ $cat->id }}">
                <i class="fa-solid fa-leaf me-2"></i>{{ $cat->name }}
            </button>
        @endforeach
    </div>

    {{-- ============ PRODUCT GRID ============ --}}
    <div class="row g-3" id="productsGrid">
        @php
            $allProducts = collect();
            foreach ($productsByCategory as $catId => $list) {
                foreach ($list as $p) $allProducts->push($p);
            }
        @endphp

        @forelse ($allProducts as $p)
            @include('site.partials.product-card', ['product' => $p])
        @empty
            <div class="no-results">
                <i class="fa-regular fa-face-frown d-block"></i>
                No products yet. Add some from the admin panel.
            </div>
        @endforelse
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const grid = document.getElementById('productsGrid');
    const search = document.getElementById('searchInput');
    let currentCat = 'all';
    let currentQuery = '';

    function filter() {
        const q = currentQuery.toLowerCase().trim();
        let shown = 0;

        grid.querySelectorAll('[data-product]').forEach(card => {
            const matchCat = currentCat === 'all' || card.dataset.cat === String(currentCat);
            const name = (card.dataset.name || '').toLowerCase();
            const matchQ = !q || name.includes(q);
            const visible = matchCat && matchQ;

            card.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });

        // Show/hide empty state
        const noRes = grid.querySelector('.no-results-dynamic');
        if (shown === 0) {
            if (!noRes) {
                const el = document.createElement('div');
                el.className = 'no-results no-results-dynamic';
                el.innerHTML = '<i class="fa-regular fa-face-frown d-block"></i>No products match your search.';
                grid.appendChild(el);
            }
        } else if (noRes) {
            noRes.remove();
        }
    }

    // Category click
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCat = btn.dataset.cat;
            filter();
        });
    });

    // Search
    let searchTimer;
    search.addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentQuery = e.target.value;
            filter();
        }, 150);
    });

    // Add to cart
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-add-to-cart]');
        if (!btn) return;
        e.preventDefault();
        const id = parseInt(btn.dataset.addToCart);
        if (!id) return;

        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const { ok } = await window.GS.addToCart(id, 1);

        if (ok) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Added';
            setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 1100);
        } else {
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
