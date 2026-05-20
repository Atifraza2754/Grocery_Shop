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

    /* Mobile delivery banner (shows only on small screens) */
    .mobile-delivery-banner {
        background: #a83b2b; /* deep red similar to site banner */
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 14px;
        text-align: center;
    }
    .msg{
        margin-top:-15px;
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
    display: block; /* Flex display ko khatam karein taake center na ho */
    overflow: hidden;
    background: #f8f9fa;
}
.product-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: contain; /* Taake image pure container ko cover kare jese pehli image mein hai */
    padding: 0 !important; /* Inner padding ko khatam karein */
}
    .product-card:hover .product-img { transform: scale(1.05); }
    .product-fallback {
        font-size: 3rem; color: #ccc;
    }
    .product-body {
        padding: .85rem; display: flex; flex-direction: column; flex: 1;
    }
    .product-title {
        font-size: 14px; font-weight: 600; margin-bottom: 4px;
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

    /* Quantity control shown after initial Add-to-cart on product cards */
    .cart-qty-wrap {
        display: inline-flex; align-items: center; justify-content: space-between;
        gap: 8px; width: 100%; background: rgba(46,125,50,0.06);
        padding: 6px; border-radius: 9px; box-sizing: border-box;
    }
    .cart-qty-wrap .qty-btn {
        border: none; background: transparent; color: var(--gs-primary-dark);
        width: 42px; height: 42px; border-radius: 8px; 
        font-weight: 900; /* Isko extra bold kar diya hai */
        font-size: 1.6rem; /* Plus aur minus ka size bara kar diya hai */
        display: inline-flex; align-items: center; justify-content: center;
        line-height: 1; 
    }
    .cart-qty-wrap .qty-value {
        min-width: 30px; text-align: center; font-weight: 800; font-size: 1.1rem; color: var(--gs-primary-dark);
    }

    /* No-results */
    .no-results {
        text-align: center; padding: 50px 20px;
        color: var(--gs-muted); width: 100%;
    }
    .no-results i { font-size: 2.5rem; margin-bottom: .5rem; opacity: .5; }

   @media (max-width: 767px) {
        .hero {
            display: none !important;
        }
        
        /* Force 2 columns on mobile */
        #productsGrid > div {
            width: 50% !important;
        }

        /* Compact Product Card for Mobile */
        .product-img-wrap {
            height: 100px; /* Image height kam kar di */
            padding: 6px;
        }
        .product-body {
            padding: 0.5rem; /* Padding choti kar di */
        }
        .product-title {
            font-size: 0.85rem; /* Title font chota kiya */
            line-height: 1.2;
            min-height: 2.4em; /* 2 lines ke liye fix space */
            margin-bottom: 4px;
        }
        .product-price {
            font-size: 0.95rem; /* Price thori choti ki */
            margin-bottom: 0.4rem;
        }
        .product-price .unit {
            font-size: 0.7rem;
        }
        .stock-pill {
            font-size: 0.65rem;
            padding: 2px 6px;
            margin-bottom: 4px;
        }
        .btn-add {
            padding: 5px 0; /* Button ki height kam kar di */
            font-size: 0.85rem;
            border-radius: 6px;
        }

        /* Adjusted Qty Buttons for Mobile */
        .cart-qty-wrap {
            padding: 4px;
        }
        .cart-qty-wrap .qty-btn {
            width: 36px; height: 36px;
            font-size: 1.4rem; /* Mobile pe bhi bold aur wazeh dikhega */
        }
        .cart-qty-wrap .qty-value {
            font-size: 1rem;
        }

    }
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
    {{-- Mobile-only delivery notice shown above the search on small screens --}}
    <div class="d-block d-md-none mb-2 msg">
        <div class="mobile-delivery-banner">
            Order before 9pm for next-day delivery; Karachi only, no same-day service.
        </div>
    </div>

    <div class="search-container mb-3">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
    </div>

    {{-- ============ CATEGORY TABS ============ --}}
    <div class="category-container mb-4" id="categoryTabs">
        @foreach ($categories as $cat)
            <button class="category-btn {{ $loop->first ? 'active' : '' }}" data-cat="{{ $cat->id }}">
                <i class="fa-solid fa-leaf me-2"></i>{{ $cat->name }}
            </button>
        @endforeach
        <button class="category-btn" data-cat="grocery">
            <i class="fa-solid fa-cart-shopping me-2"></i>Grocery (custom)
        </button>
    </div>

    {{-- ============ GROCERY VIEW (custom items) ============ --}}
    <div id="groceryView" style="display:none;" class="mb-4">
        <div class="gs-card p-4" style="background:#fff;border:1px solid var(--gs-border);border-radius:14px;">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1" style="font-weight:700;color:var(--gs-primary-dark);">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Custom Grocery List
                    </h5>
                    <p class="text-muted mb-0 small">
                        Type any items you need — admin will set the prices after you place the order.
                    </p>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold mb-1">Item name</label>
                    <input type="text" id="gName" class="form-control"
                           placeholder="e.g. Daal Chana, Atta 5kg, Cooking Oil"
                           style="border-radius:10px;">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Qty</label>
                    <input type="number" id="gQty" class="form-control" value="1" min="0.1" step="0.1"
                           style="border-radius:10px;">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Unit</label>
                    <select id="gUnit" class="form-select" style="border-radius:10px;">
                        <option value="">Select an option</option>
                        
                        <option value="piece">Piece</option>
                        <option value="pack">Pack</option>
                        <option value="dozen">Dozen</option>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="gram">Gram (g)</option>
                        <option value="100gm">100 gm</option>
                        <option value="250gm">250 gm</option>
                        <option value="500gm">500 gm</option>
                        <option value="750gm">750 gm</option>
                        <option value="litre">Litre (l)</option>
                        <option value="half_liter">Half Liter</option>
                        <option value="ml">Millilitre (ml)</option>

                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn-primary-gs w-100" id="gAddBtn" style="border-radius:10px;">
                        <i class="fa-solid fa-plus me-1"></i> Add
                    </button>
                </div>
            </div>

            <div class="mt-4" id="gListWrap">
                {{-- Grocery items list rendered here --}}
            </div>

            <div class="text-muted small mt-3" id="gEmptyText" style="display:none;">
                No items yet — add items above to get started.
            </div>

            <div class="d-flex gap-2 mt-3" id="gActions" style="display:none !important;">
                <a href="{{ route('site.cart') }}" class="btn-primary-gs text-decoration-none flex-grow-1 text-center">
                    <i class="fa-solid fa-basket-shopping me-1"></i> View cart &amp; checkout
                </a>
            </div>
        </div>
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
    const groceryView = document.getElementById('groceryView');
    
    // Automatically get the active category on initial load
    const activeBtn = document.querySelector('.category-btn.active');
    let currentCat = activeBtn ? activeBtn.dataset.cat : '';
    let currentQuery = '';

    function filter() {
        const q = currentQuery.toLowerCase().trim();
        let shown = 0;

        grid.querySelectorAll('[data-product]').forEach(card => {
            const matchCat = card.dataset.cat === String(currentCat);
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

    function showGrocery(on) {
        if (on) {
            groceryView.style.display = '';
            grid.style.display = 'none';
            search.parentElement.style.display = 'none';
        } else {
            groceryView.style.display = 'none';
            grid.style.display = '';
            search.parentElement.style.display = '';
        }
    }

    // Open product details in modal (AJAX)
    async function openProductModal(url) {
        const modalEl = document.getElementById('productModal');
        const modalContent = document.getElementById('productModalContent');
        modalContent.innerHTML = '<div class="p-4 text-center"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Failed');
            const html = await res.text();
            modalContent.innerHTML = html;
            const bs = new bootstrap.Modal(modalEl);
            bs.show();
            initProductModal(modalContent, bs);
        } catch (err) {
            modalContent.innerHTML = '<div class="p-4 text-center text-danger">Could not load product details.</div>';
            const bs = new bootstrap.Modal(modalEl);
            bs.show();
        }
    }

    // Initialize behaviours for content injected into the modal
    function initProductModal(rootEl, bsInstance) {
        if (!rootEl) return;
        const modalRoot = rootEl.querySelector('.product-modal-root') || rootEl;

        // Gallery thumbs
        const main = modalRoot.querySelector('#pdMainImg');
        modalRoot.querySelectorAll('.pd-thumb').forEach(t => {
            t.addEventListener('click', () => {
                modalRoot.querySelectorAll('.pd-thumb').forEach(x => x.classList.remove('active'));
                t.classList.add('active');
                if (main) main.src = t.dataset.img;
            });
        });

        // Qty stepper + visible selected display
        const qty = modalRoot.querySelector('#qtyInput');
        const max = parseInt(qty?.max || '999');
        const qtyDisplay = modalRoot.querySelector('#qtyDisplay');

        function updateQtyDisplay() {
            if (!qtyDisplay || !qty) return;
            const v = parseInt(qty.value || '1');
            qtyDisplay.textContent = v + ' Selected';
        }

        modalRoot.querySelector('#qtyMinus')?.addEventListener('click', () => {
            const v = Math.max(1, parseInt(qty.value || '1') - 1);
            qty.value = v;
            updateQtyDisplay();
        });
        modalRoot.querySelector('#qtyPlus')?.addEventListener('click', () => {
            const v = Math.min(max, parseInt(qty.value || '1') + 1);
            qty.value = v;
            updateQtyDisplay();
        });
        // initialize display
        updateQtyDisplay();

        // Add to cart from modal
        modalRoot.querySelector('#pdAddBtn')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const q = Math.max(1, parseInt(qty.value || '1'));
            btn.disabled = true;
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Adding...';

            const productId = modalRoot.dataset.productId || modalRoot.querySelector('.product-modal-root')?.dataset.productId;
            const { ok, data } = await window.GS.addToCart(parseInt(productId), q);

            if (ok) {
                btn.innerHTML = '<i class="fa-solid fa-check me-2"></i> Added to cart';
                // update grid card if present
                try {
                    const card = document.querySelector('#productsGrid a.product-card[data-product-id="' + productId + '"]');
                    if (card) {
                        const existingQtyWrap = card.querySelector('.cart-qty-wrap');
                        if (existingQtyWrap) {
                            const qtyEl = existingQtyWrap.querySelector('.qty-value');
                            qtyEl.textContent = (parseInt(qtyEl.textContent || '0') + q).toString();
                            existingQtyWrap.setAttribute('data-in-cart', 'true');
                            if (!existingQtyWrap.dataset.maxAdd) {
                                const sourceMax = card.dataset.maxAdd || card.querySelector('[data-add-to-cart]')?.dataset?.maxAdd || '999';
                                existingQtyWrap.dataset.maxAdd = sourceMax;
                            }
                        } else {
                            const wrap = document.createElement('div');
                            wrap.className = 'cart-qty-wrap';
                            wrap.setAttribute('data-in-cart', 'true');
                            const sourceMax = card.dataset.maxAdd || card.querySelector('[data-add-to-cart]')?.dataset?.maxAdd || '999';
                            wrap.setAttribute('data-max-add', sourceMax);
                            wrap.innerHTML = `\n  <button class="qty-btn" data-cart-dec="${productId}" type="button">-</button>\n                                
                            <div class="qty-value">${q}</div>\n                                <button class="qty-btn" data-cart-inc="${productId}" type="button">+</button>\n                            `;
                            const btn = card.querySelector('[data-add-to-cart]');
                            if (btn) btn.replaceWith(wrap);
                        }
                    }
                } catch (err) { /* ignore */ }

                setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 1300);
            } else {
                btn.innerHTML = orig;
                btn.disabled = false;
            }
        });

        // Make related cards open the modal too
        modalRoot.querySelectorAll('a.product-card[data-product-slug]').forEach(a => {
            a.addEventListener('click', (ev) => {
                ev.preventDefault();
                openProductModal(a.href);
            });
        });
    }

    // Category click
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCat = btn.dataset.cat;
            if (currentCat === 'grocery') { showGrocery(true); }
            else { showGrocery(false); filter(); }
        });
    });

    /* ===== Grocery list (custom items) ===== */
    const gListWrap = document.getElementById('gListWrap');
    const gEmpty = document.getElementById('gEmptyText');
    const gActions = document.getElementById('gActions');

    function renderGroceryList(items) {
        if (!items.length) {
            gListWrap.innerHTML = '';
            gEmpty.style.display = '';
            gActions.style.display = 'none !important';
            gActions.setAttribute('style', 'display:none !important;');
            return;
        }
        gEmpty.style.display = 'none';
        gActions.removeAttribute('style');

        gListWrap.innerHTML = `
            <div style="border:1px solid var(--gs-border);border-radius:10px;overflow:hidden;">
                ${items.map((it, idx) => `
                    <div class="d-flex align-items-center gap-2 p-2 ${idx > 0 ? 'border-top' : ''}" data-gid="${it.id}">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${escapeHtml(it.name)}</div>
                            <div class="text-muted small">${it.qty} ${escapeHtml(it.unit)}</div>
                        </div>
                        <button class="btn btn-sm btn-link text-danger p-1" data-gremove="${it.id}" title="Remove">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    document.getElementById('gAddBtn').addEventListener('click', async () => {
        const name = document.getElementById('gName').value.trim();
        const qty  = parseFloat(document.getElementById('gQty').value || '1');
        const unit = document.getElementById('gUnit').value;

        if (!name) { window.GS.toast('Enter an item name', 'warn'); return; }

        const { ok, data } = await window.GS.post(window.GS.urls.cartGroceryAdd, { name, qty, unit });
        if (ok) {
            renderGroceryList(data.items || []);
            window.GS.setCartCount(data.count);
            window.GS.toast('Added: ' + name);
            document.getElementById('gName').value = '';
            document.getElementById('gQty').value = '1';
            document.getElementById('gName').focus();
        } else {
            window.GS.toast(data.message || 'Could not add', 'error');
        }
    });

    gListWrap.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-gremove]');
        if (!btn) return;
        const id = btn.dataset.gremove;
        const { ok, data } = await window.GS.post(window.GS.urls.cartGroceryRemove, { id });
        if (ok) {
            renderGroceryList(data.items || []);
            window.GS.setCartCount(data.count);
            window.GS.toast('Removed');
        }
    });

    // Initial load: render any grocery items already in cart
    renderGroceryList(@json(app(\App\Services\CartService::class)->groceryItems()));

    // Search
    let searchTimer;
    search.addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentQuery = e.target.value;
            filter();
        }, 150);
    });

    // Add to cart: on the home product grid transform Add -> qty controls, elsewhere keep quick-add
    document.addEventListener('click', async (e) => {
        // Increment button in qty control
        const inc = e.target.closest('[data-cart-inc]');
        if (inc) {
            e.preventDefault();
            const pid = parseInt(inc.dataset.cartInc);
            const wrap = inc.closest('.cart-qty-wrap');
            const qtyEl = wrap && wrap.querySelector('.qty-value');
            let qty = parseInt(qtyEl ? qtyEl.textContent : '0') || 0;

            // respect per-product max-add (min of low_stock_threshold and stock_qty)
            const maxAdd = parseInt(wrap?.dataset.maxAdd || wrap?.closest('a.product-card')?.dataset.maxAdd || '999', 10);
            if (qty >= maxAdd) {
                window.GS.toast('Maximum allowed for this product is ' + maxAdd, 'warn');
                return;
            }

            try {
                inc.disabled = true;
                const { ok, data } = await window.GS.addToCart(pid, 1);
                if (ok) {
                    qty++;
                    if (qtyEl) qtyEl.textContent = qty;
                    // mark as present in cart after successful first add
                    if (wrap) wrap.setAttribute('data-in-cart', 'true');
                    if (data && data.count !== undefined) window.GS.setCartCount(data.count);
                }
            } finally { inc.disabled = false; }
            return;
        }

        // Decrement / remove button in qty control
        const dec = e.target.closest('[data-cart-dec]');
        if (dec) {
            e.preventDefault();
            const pid = parseInt(dec.dataset.cartDec);
            const wrap = dec.closest('.cart-qty-wrap');
            const qtyEl = wrap && wrap.querySelector('.qty-value');
            let qty = parseInt(qtyEl ? qtyEl.textContent : '0') || 0;
            const inCart = wrap && wrap.getAttribute('data-in-cart') === 'true';

            if (!inCart) {
                // not yet added on server — simply restore the Add button locally
                const addBtnHtml = `<button class="btn-add" type="button" data-add-to-cart="${pid}" onclick="event.preventDefault();"><i class="fa-solid fa-plus me-1"></i> Add to Cart</button>`;
                const el = document.createElement('div');
                el.innerHTML = addBtnHtml;
                wrap.replaceWith(el.firstElementChild);
                return;
            }

            if (qty <= 1) {
                // remove from cart on server
                try {
                    dec.disabled = true;
                    const { ok, data } = await window.GS.post(window.GS.urls.cartRemove, { product_id: pid });
                    if (ok) {
                        const addBtnHtml = `<button class="btn-add" type="button" data-add-to-cart="${pid}" onclick="event.preventDefault();"><i class="fa-solid fa-plus me-1"></i> Add to Cart</button>`;
                        const el = document.createElement('div');
                        el.innerHTML = addBtnHtml;
                        wrap.replaceWith(el.firstElementChild);
                        if (data && data.count !== undefined) window.GS.setCartCount(data.count);
                    }
                } finally { dec.disabled = false; }
            } else {
                // update to new qty
                const newQty = qty - 1;
                try {
                    dec.disabled = true;
                    const { ok, data } = await window.GS.post(window.GS.urls.cartUpdate, { product_id: pid, qty: newQty });
                    if (ok) {
                        if (qtyEl) qtyEl.textContent = newQty;
                        if (data && data.count !== undefined) window.GS.setCartCount(data.count);
                    }
                } finally { dec.disabled = false; }
            }
            return;
        }

        // Product card click -> open modal (ignore clicks on interactive elements)
        const cardLink = e.target.closest('a.product-card[data-product-slug]');
        if (cardLink) {
            // if click was on interactive element inside the card, let other handlers manage it
            if (e.target.closest('[data-add-to-cart]') || e.target.closest('[data-cart-inc]') || e.target.closest('[data-cart-dec]') || e.target.closest('.qty-btn') || e.target.closest('button')) {
                // allow existing handlers to run
            } else {
                e.preventDefault();
                openProductModal(cardLink.href);
                return;
            }
        }

        // Initial Add-to-cart click
        const btn = e.target.closest('[data-add-to-cart]');
        if (!btn) return;
        e.preventDefault();
        const id = parseInt(btn.dataset.addToCart);
        if (!id) return;

        // Only transform into a qty control for cards inside the home products grid
        const inProductsGrid = btn.closest('#productsGrid');
        if (!inProductsGrid) {
            // fallback: keep previous behaviour on other pages
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
            return;
        }

        // For product cards in the grid: do NOT call server yet — just swap to qty control UI
        const wrap = document.createElement('div');
        wrap.className = 'cart-qty-wrap';
        const maxAddFromBtn = btn?.dataset?.maxAdd;
        const maxAddFromCard = btn?.closest('a.product-card')?.dataset?.maxAdd;
        const finalMaxAdd = maxAddFromBtn || maxAddFromCard || '999';
        wrap.setAttribute('data-max-add', finalMaxAdd);
        wrap.setAttribute('data-in-cart', 'false');
        wrap.innerHTML = `
            <button class="qty-btn" data-cart-dec="${id}" type="button">-</button>
            <div class="qty-value">0</div>
            <button class="qty-btn" data-cart-inc="${id}" type="button">+</button>
        `;
        btn.replaceWith(wrap);
    });

    // Execute filter immediately on load to show only the default active category
    if (currentCat && currentCat !== 'grocery') {
        filter();
    }
})();
</script>
@endpush