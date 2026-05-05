@extends('site.layouts.app')

@section('title', 'Your Cart')

@push('styles')
<style>
    .cart-card { background: #fff; border: 1px solid var(--gs-border); border-radius: 14px; }
    .cart-row {
        display: flex; align-items: center; gap: 1rem;
        padding: 1rem; border-bottom: 1px solid var(--gs-border);
    }
    .cart-row:last-child { border-bottom: none; }
    .cart-thumb {
        width: 72px; height: 72px; border-radius: 10px;
        background: #f8f9fa; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .cart-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 6px; }
    .cart-info { flex: 1; min-width: 0; }
    .cart-name {
        font-weight: 600; color: var(--gs-text); text-decoration: none;
        display: block; margin-bottom: 2px;
    }
    .cart-name:hover { color: var(--gs-primary); }
    .cart-meta { color: var(--gs-muted); font-size: .85rem; }
    .cart-line-total { font-weight: 700; color: var(--gs-primary); white-space: nowrap; }

    .qty-stepper { display: inline-flex; border: 1.5px solid var(--gs-border); border-radius: 999px; overflow: hidden; }
    .qty-stepper button {
        background: #fff; border: none; padding: 4px 10px; font-weight: 700;
        color: var(--gs-primary); cursor: pointer;
    }
    .qty-stepper button:hover { background: var(--gs-primary-soft); }
    .qty-stepper input {
        border: none; width: 40px; text-align: center; font-weight: 700; height: 32px; outline: none;
    }

    .summary-row {
        display: flex; justify-content: space-between;
        padding: .35rem 0;
    }
    .summary-row.total {
        border-top: 1px dashed var(--gs-border);
        padding-top: .85rem; margin-top: .35rem;
        font-weight: 800; font-size: 1.15rem;
    }

    .empty-cart {
        text-align: center; padding: 3rem 1rem;
        color: var(--gs-muted);
    }
    .empty-cart i { font-size: 4rem; color: #d0d7de; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="m-0" style="font-weight: 700;">
            <i class="fa-solid fa-basket-shopping me-2" style="color: var(--gs-primary);"></i>Your Cart
        </h2>
        <a href="{{ route('site.home') }}" class="btn btn-outline-gs">
            <i class="fa-solid fa-arrow-left me-1"></i> Continue shopping
        </a>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (count($items) === 0)
        <div class="cart-card empty-cart">
            <i class="fa-solid fa-basket-shopping"></i>
            <h5>Your cart is empty</h5>
            <p>Add some fresh groceries to get started.</p>
            <a href="{{ route('site.home') }}" class="btn-primary-gs d-inline-block mt-2 text-decoration-none">
                Browse products
            </a>
        </div>
    @else
        <div class="row g-4">

            {{-- ITEMS --}}
            <div class="col-lg-8">
                <div class="cart-card" id="cartItems">
                    @foreach ($items as $row)
                        @php
                            $img = $row->product->image
                                ? \Illuminate\Support\Facades\Storage::url($row->product->image)
                                : null;
                        @endphp
                        <div class="cart-row" data-pid="{{ $row->product->id }}">
                            <div class="cart-thumb">
                                @if ($img)
                                    <img src="{{ $img }}" alt="{{ $row->product->name }}">
                                @else
                                    <i class="fa-solid fa-image" style="color:#ccc; font-size:1.5rem;"></i>
                                @endif
                            </div>

                            <div class="cart-info">
                                <a href="{{ route('site.product.show', $row->product->slug) }}" class="cart-name">
                                    {{ $row->product->name }}
                                </a>
                                <div class="cart-meta">
                                    Rs {{ number_format($row->price, 0) }} / {{ $row->product->unit }}
                                </div>
                            </div>

                            <div class="qty-stepper">
                                <button type="button" data-qty-minus>−</button>
                                <input type="number"
                                       data-qty-input
                                       value="{{ rtrim(rtrim((string) $row->qty, '0'), '.') }}"
                                       min="0">
                                <button type="button" data-qty-plus>+</button>
                            </div>

                            <div class="cart-line-total" data-line-total>
                                Rs {{ number_format($row->line_total, 0) }}
                            </div>

                            <button class="btn btn-link text-danger p-1" data-remove title="Remove">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- SUMMARY --}}
            <div class="col-lg-4">
                <div class="cart-card p-3" style="position: sticky; top: 90px;">
                    <h5 class="mb-3" style="font-weight: 700;">Order Summary</h5>

                    {{-- Coupon --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Have a coupon?</label>
                        <div class="input-group">
                            <input type="text" id="couponInput" class="form-control"
                                   placeholder="Enter code"
                                   value="{{ $totals['coupon_code'] ?? '' }}"
                                   style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-outline-gs" id="couponApply" style="border-radius: 0 10px 10px 0;">Apply</button>
                        </div>
                        <div id="couponMsg" class="small mt-1"></div>
                    </div>

                    <div id="summaryRows">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span data-summary="subtotal">Rs {{ number_format($totals['subtotal'], 0) }}</span>
                        </div>
                        <div class="summary-row" id="discountRow" style="{{ $totals['discount'] > 0 ? '' : 'display:none;' }}">
                            <span>Discount</span>
                            <span class="text-success">- Rs <span data-summary="discount">{{ number_format($totals['discount'], 0) }}</span></span>
                        </div>
                        <div class="summary-row text-muted small">
                            <span>Delivery</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total (before delivery)</span>
                            <span>Rs <span data-summary="total">{{ number_format($totals['subtotal'] - $totals['discount'], 0) }}</span></span>
                        </div>
                    </div>

                    <a href="{{ route('site.checkout') }}" class="btn-primary-gs w-100 mt-3 text-decoration-none text-center d-block">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    /* ===== qty change ===== */
    let timer;

    function updateRow(row) {
        const pid = parseInt(row.dataset.pid);
        const input = row.querySelector('[data-qty-input]');
        const qty = Math.max(0, parseInt(input.value || '0'));
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const { ok, data } = await window.GS.post(window.GS.urls.cartUpdate, {
                product_id: pid, qty,
            });
            if (ok) {
                if (qty === 0) {
                    row.remove();
                    if (!document.querySelector('[data-pid]')) location.reload();
                } else {
                    refreshSummary(data.totals);
                }
                window.GS.setCartCount(data.count);
            }
        }, 250);
    }

    document.querySelectorAll('.cart-row').forEach(row => {
        const input = row.querySelector('[data-qty-input]');

        row.querySelector('[data-qty-minus]').addEventListener('click', () => {
            input.value = Math.max(0, parseInt(input.value || '1') - 1);
            updateRow(row);
        });
        row.querySelector('[data-qty-plus]').addEventListener('click', () => {
            input.value = parseInt(input.value || '0') + 1;
            updateRow(row);
        });
        input.addEventListener('change', () => updateRow(row));

        row.querySelector('[data-remove]').addEventListener('click', async () => {
            const pid = parseInt(row.dataset.pid);
            const { ok, data } = await window.GS.post(window.GS.urls.cartRemove, { product_id: pid });
            if (ok) {
                row.remove();
                window.GS.setCartCount(data.count);
                window.GS.toast('Removed from cart');
                if (!document.querySelector('[data-pid]')) location.reload();
                else refreshSummary(data.totals);
            }
        });
    });

    /* ===== coupon ===== */
    document.getElementById('couponApply')?.addEventListener('click', async () => {
        const code = document.getElementById('couponInput').value.trim();
        const msg = document.getElementById('couponMsg');
        const { ok, data } = await window.GS.post(window.GS.urls.cartCoupon, { code });
        msg.textContent = data.message || '';
        msg.className = 'small mt-1 ' + (ok ? 'text-success' : 'text-danger');
        if (ok) {
            window.GS.toast(data.message || 'Coupon applied');
            refreshSummary(data.totals);
        }
    });

    function refreshSummary(t) {
        if (!t) return;
        document.querySelector('[data-summary="subtotal"]').textContent = 'Rs ' + Math.round(t.subtotal).toLocaleString();
        document.querySelector('[data-summary="discount"]').textContent = Math.round(t.discount).toLocaleString();
        document.querySelector('[data-summary="total"]').textContent    = Math.round(t.subtotal - t.discount).toLocaleString();
        document.getElementById('discountRow').style.display = t.discount > 0 ? '' : 'none';

        // Refresh line totals
        document.querySelectorAll('.cart-row').forEach(row => {
            const qty = parseFloat(row.querySelector('[data-qty-input]').value || '0');
            // Get unit price from .cart-meta (Rs X / unit)
            const metaText = row.querySelector('.cart-meta').textContent;
            const m = metaText.match(/Rs\s+([\d,]+)/);
            if (m) {
                const unit = parseFloat(m[1].replace(/,/g, ''));
                row.querySelector('[data-line-total]').textContent = 'Rs ' + Math.round(unit * qty).toLocaleString();
            }
        });
    }
})();
</script>
@endpush
